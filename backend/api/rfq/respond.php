<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/role_check.php';
requireRole('fournisseur');

require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (
    !$data ||
    !isset($data['rfq_id']) ||
    !isset($data['quoted_price'])
) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: rfq_id, quoted_price']);
    exit;
}

$rfqId = (int)$data['rfq_id'];
$quotedPrice = (float)$data['quoted_price'];
$leadTimeDays = isset($data['lead_time_days']) ? (int)$data['lead_time_days'] : null;
$supplierNote = $data['supplier_note'] ?? null;

if ($quotedPrice <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'quoted_price must be greater than 0']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Verify RFQ exists and belongs to this supplier user
    $rfqStmt = $pdo->prepare("
        SELECT id, supplier_id, revision_id, status
        FROM rfq_requests
        WHERE id = :id
        LIMIT 1
    ");
    $rfqStmt->execute(['id' => $rfqId]);
    $rfq = $rfqStmt->fetch(PDO::FETCH_ASSOC);

    if (!$rfq) {
        throw new Exception('RFQ not found');
    }

    if ((int)$rfq['supplier_id'] !== (int)$_SESSION['user_id']) {
        throw new Exception('Not authorized to respond to this RFQ');
    }

    if (in_array($rfq['status'], ['accepted', 'rejected', 'expired'], true)) {
        throw new Exception('RFQ is closed and cannot be revised');
    }

    // 2. Determine next revision number
    $revStmt = $pdo->prepare("
        SELECT COALESCE(MAX(revision_id), 0) AS max_revision
        FROM rfq_revisions
        WHERE rfq_id = :rfq_id
          AND supplier_id = :supplier_id
    ");
    $revStmt->execute([
        'rfq_id' => $rfqId,
        'supplier_id' => $_SESSION['user_id']
    ]);
    $revRow = $revStmt->fetch(PDO::FETCH_ASSOC);

    $nextRevision = ((int)$revRow['max_revision']) + 1;

    // 3. Insert revision history
    $insertRevision = $pdo->prepare("
        INSERT INTO rfq_revisions
        (
            rfq_id,
            revision_id,
            supplier_id,
            quoted_price,
            lead_time_days,
            supplier_note
        )
        VALUES
        (
            :rfq_id,
            :revision_id,
            :supplier_id,
            :quoted_price,
            :lead_time_days,
            :supplier_note
        )
    ");

    $insertRevision->execute([
        'rfq_id' => $rfqId,
        'revision_id' => $nextRevision,
        'supplier_id' => $_SESSION['user_id'],
        'quoted_price' => $quotedPrice,
        'lead_time_days' => $leadTimeDays,
        'supplier_note' => $supplierNote
    ]);

    // 4. Update main RFQ current state
    $updateRfq = $pdo->prepare("
        UPDATE rfq_requests
        SET
            quoted_price = :quoted_price,
            supplier_message = :supplier_message,
            status = 'quoted',
            revision_id = :revision_id
        WHERE id = :id
    ");

    $updateRfq->execute([
        'quoted_price' => $quotedPrice,
        'supplier_message' => $supplierNote,
        'revision_id' => $nextRevision,
        'id' => $rfqId
    ]);

    $pdo->commit();

    echo json_encode([
        'message' => 'RFQ responded successfully',
        'rfq_id' => $rfqId,
        'revision_id' => $nextRevision,
        'quoted_price' => $quotedPrice,
        'lead_time_days' => $leadTimeDays
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    $pdo->rollBack();

    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}