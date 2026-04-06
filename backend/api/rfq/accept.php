<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SESSION['role'] !== 'owner') {
    http_response_code(403);
    echo json_encode(['error' => 'Only owners can accept RFQs']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['rfq_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required field: rfq_id']);
    exit;
}

$rfqId = (int)$data['rfq_id'];
$decisionNote = isset($data['decision_note']) ? trim((string)$data['decision_note']) : null;

if ($rfqId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid rfq_id']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Fetch RFQ
    $stmt = $pdo->prepare("
        SELECT id, status, quoted_price, revision_id, supplier_id, client_id
        FROM rfq_requests
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $rfqId]);
    $rfq = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rfq) {
        throw new Exception('RFQ not found');
    }

    // 2. Only quoted RFQs can be accepted
    if ($rfq['status'] !== 'quoted') {
        throw new Exception("Only quoted RFQs can be accepted. Current status: {$rfq['status']}");
    }

    // 3. Update RFQ status
    $updateStmt = $pdo->prepare("
        UPDATE rfq_requests
        SET
            status = 'accepted',
            updated_at = NOW()
        WHERE id = :id
    ");
    $updateStmt->execute(['id' => $rfqId]);

    $pdo->commit();

    echo json_encode([
        'message' => 'RFQ accepted successfully',
        'rfq_id' => $rfqId,
        'previous_status' => 'quoted',
        'new_status' => 'accepted',
        'quoted_price' => isset($rfq['quoted_price']) ? (float)$rfq['quoted_price'] : null,
        'revision_id' => isset($rfq['revision_id']) ? (int)$rfq['revision_id'] : null,
        'decision_note' => $decisionNote
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}