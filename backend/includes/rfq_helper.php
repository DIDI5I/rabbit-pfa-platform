<?php

function createAutoDraftRfq(PDO $pdo, int $componentId, int $ownerUserId): ?int
{
    // 1. Get component info
    $componentStmt = $pdo->prepare("
        SELECT id, name, stock_qty, low_stock_threshold
        FROM components
        WHERE id = :id
          AND is_active = TRUE
        LIMIT 1
    ");
    $componentStmt->execute(['id' => $componentId]);
    $component = $componentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$component) {
        return null;
    }

    $stockQty = (float)$component['stock_qty'];
    $threshold = (float)$component['low_stock_threshold'];

    // Only trigger when below threshold
    if ($stockQty >= $threshold) {
        return null;
    }

    // 2. Prevent duplicate auto RFQs
    $existingStmt = $pdo->prepare("
        SELECT id
        FROM rfq_requests
        WHERE component_id = :component_id
          AND auto_triggered = TRUE
          AND status IN ('draft', 'open', 'quoted')
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $existingStmt->execute(['component_id' => $componentId]);
    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        return null;
    }

    // 3. Get preferred supplier company from part_sources
    $sourceStmt = $pdo->prepare("
        SELECT supplier_id, unit_cost, lead_time_days
        FROM part_sources
        WHERE component_id = :component_id
          AND is_preferred = TRUE
        LIMIT 1
    ");
    $sourceStmt->execute(['component_id' => $componentId]);
    $source = $sourceStmt->fetch(PDO::FETCH_ASSOC);

    if (!$source) {
        return null;
    }

    $supplierCompanyId = (int)$source['supplier_id'];

    // 4. Compute suggested reorder quantity
    $suggestedQty = max(1, (int)ceil(($threshold * 2) - $stockQty));

    // 5. Create draft RFQ
    $insertStmt = $pdo->prepare("
        INSERT INTO rfq_requests
        (
            client_id,
            component_id,
            supplier_id,
            quantity_requested,
            status,
            client_message,
            deadline_date,
            revision_id,
            is_blind,
            auto_triggered
        )
        VALUES
        (
            :client_id,
            :component_id,
            :supplier_id,
            :quantity_requested,
            'draft',
            :client_message,
            NULL,
            1,
            FALSE,
            TRUE
        )
    ");

    $insertStmt->execute([
        'client_id' => $ownerUserId,
        'component_id' => $componentId,
        'supplier_id' => $supplierCompanyId,
        'quantity_requested' => $suggestedQty,
        'client_message' => 'Auto-triggered draft RFQ created because stock dropped below threshold.'
    ]);

    return (int)$pdo->lastInsertId();
}

function getRfqById(PDO $pdo, int $rfqId): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM rfq_requests
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $rfqId]);

    $rfq = $stmt->fetch(PDO::FETCH_ASSOC);
    return $rfq ?: null;
}

function assertRfqExists(?array $rfq): void
{
    if (!$rfq) {
        throw new Exception('RFQ not found');
    }
}

function openRfq(PDO $pdo, int $rfqId): array
{
    $rfq = getRfqById($pdo, $rfqId);
    assertRfqExists($rfq);

    if ($rfq['status'] !== 'draft') {
        throw new Exception("Only draft RFQs can be opened. Current status: {$rfq['status']}");
    }

    $stmt = $pdo->prepare("
        UPDATE rfq_requests
        SET status = 'open',
            updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute(['id' => $rfqId]);

    return [
        'rfq_id' => $rfqId,
        'previous_status' => 'draft',
        'new_status' => 'open',
        'auto_triggered' => isset($rfq['auto_triggered']) ? (bool)$rfq['auto_triggered'] : false
    ];
}

function acceptRfq(PDO $pdo, int $rfqId, int $ownerUserId, ?string $note = null): array
{
    $rfq = getRfqById($pdo, $rfqId);
    assertRfqExists($rfq);

    if ($rfq['status'] !== 'quoted') {
        throw new Exception("Only quoted RFQs can be accepted. Current status: {$rfq['status']}");
    }

    $stmt = $pdo->prepare("
        UPDATE rfq_requests
        SET status = 'accepted',
            decision_note = :decision_note,
            decided_by = :decided_by,
            decided_at = NOW(),
            updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        'id' => $rfqId,
        'decision_note' => $note,
        'decided_by' => $ownerUserId
    ]);

    return [
        'rfq_id' => $rfqId,
        'previous_status' => 'quoted',
        'new_status' => 'accepted',
        'quoted_price' => isset($rfq['quoted_price']) ? (float)$rfq['quoted_price'] : null,
        'revision_id' => isset($rfq['revision_id']) ? (int)$rfq['revision_id'] : null,
        'decision_note' => $note,
        'decided_by' => $ownerUserId
    ];
}

function rejectRfq(PDO $pdo, int $rfqId, int $ownerUserId, ?string $note = null): array
{
    $rfq = getRfqById($pdo, $rfqId);
    assertRfqExists($rfq);

    if ($rfq['status'] !== 'quoted') {
        throw new Exception("Only quoted RFQs can be rejected. Current status: {$rfq['status']}");
    }

    $stmt = $pdo->prepare("
        UPDATE rfq_requests
        SET status = 'rejected',
            decision_note = :decision_note,
            decided_by = :decided_by,
            decided_at = NOW(),
            updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        'id' => $rfqId,
        'decision_note' => $note,
        'decided_by' => $ownerUserId
    ]);

    return [
        'rfq_id' => $rfqId,
        'previous_status' => 'quoted',
        'new_status' => 'rejected',
        'quoted_price' => isset($rfq['quoted_price']) ? (float)$rfq['quoted_price'] : null,
        'revision_id' => isset($rfq['revision_id']) ? (int)$rfq['revision_id'] : null,
        'decision_note' => $note,
        'decided_by' => $ownerUserId
    ];
}

function expireRfq(PDO $pdo, int $rfqId, int $ownerUserId, ?string $note = null): array
{
    $rfq = getRfqById($pdo, $rfqId);
    assertRfqExists($rfq);

    if (!in_array($rfq['status'], ['open', 'quoted'], true)) {
        throw new Exception("Only open or quoted RFQs can be expired. Current status: {$rfq['status']}");
    }

    $stmt = $pdo->prepare("
        UPDATE rfq_requests
        SET status = 'expired',
            decision_note = :decision_note,
            decided_by = :decided_by,
            decided_at = NOW(),
            updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        'id' => $rfqId,
        'decision_note' => $note,
        'decided_by' => $ownerUserId
    ]);

    return [
        'rfq_id' => $rfqId,
        'previous_status' => $rfq['status'],
        'new_status' => 'expired',
        'quoted_price' => isset($rfq['quoted_price']) ? (float)$rfq['quoted_price'] : null,
        'revision_id' => isset($rfq['revision_id']) ? (int)$rfq['revision_id'] : null,
        'decision_note' => $note,
        'decided_by' => $ownerUserId
    ];
}

function getAllowedRfqActions(string $role, array $rfq): array
{
    $status = $rfq['status'] ?? null;

    $actions = [
        'open' => false,
        'respond' => false,
        'accept' => false,
        'reject' => false,
        'expire' => false
    ];

    if ($role === 'owner') {
        if ($status === 'draft') {
            $actions['open'] = true;
        }

        if ($status === 'quoted') {
            $actions['accept'] = true;
            $actions['reject'] = true;
            $actions['expire'] = true;
        }

        if ($status === 'open') {
            $actions['expire'] = true;
        }
    } elseif ($role === 'fournisseur') {
        if (in_array($status, ['open', 'quoted'], true)) {
            $actions['respond'] = true;
        }
    }

    return $actions;
}