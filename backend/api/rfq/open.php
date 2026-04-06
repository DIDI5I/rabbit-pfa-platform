<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/rfq_helper.php';

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

    $result = openRfq($pdo, $rfqId);

    $pdo->commit();

    echo json_encode([
        'message' => 'RFQ opened successfully'
    ] + $result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}