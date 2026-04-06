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

    openRfq(PDO $pdo, int $rfqId): bool
    acceptRfq(PDO $pdo, int $rfqId, int $ownerUserId, ?string $note = null): bool
    rejectRfq(PDO $pdo, int $rfqId, int $ownerUserId, ?string $note = null): bool
    expireRfq(PDO $pdo, int $rfqId): bool
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