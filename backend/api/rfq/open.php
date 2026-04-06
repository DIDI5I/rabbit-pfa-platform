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
    echo json_encode(['error' => 'Only owners can open RFQs']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['rfq_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required field: rfq_id']);
    exit;
}

$rfqId = (int)$data['rfq_id'];

if ($rfqId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid rfq_id']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Fetch RFQ
    $stmt = $pdo->prepare("
        SELECT id, status, auto_triggered
        FROM rfq_requests
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $rfqId]);
    $rfq = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rfq) {
        throw new Exception('RFQ not found');
    }

    // 2. Only draft RFQs can be opened
    if ($rfq['status'] !== 'draft') {
        throw new Exception("Only draft RFQs can be opened. Current status: {$rfq['status']}");
    }

    // 3. Update status
    $updateStmt = $pdo->prepare("
        UPDATE rfq_requests
        SET
            status = 'open',
            updated_at = NOW()
        WHERE id = :id
    ");
    $updateStmt->execute(['id' => $rfqId]);

    $pdo->commit();

    echo json_encode([
        'message' => 'RFQ opened successfully',
        'rfq_id' => $rfqId,
        'previous_status' => 'draft',
        'new_status' => 'open',
        'auto_triggered' => isset($rfq['auto_triggered']) ? (bool)$rfq['auto_triggered'] : false
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