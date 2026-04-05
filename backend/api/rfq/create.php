<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/role_check.php';
requireRole('client');

require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (
    !$data ||
    !isset($data['component_id']) ||
    !isset($data['quantity'])
) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: component_id, quantity']);
    exit;
}

$componentId = (int)$data['component_id'];
$quantity = (float)$data['quantity'];
$clientMessage = $data['message'] ?? null;
$deadlineDate = $data['deadline_date'] ?? null;
$status = $data['status'] ?? 'open';
$autoTriggered = isset($data['auto_triggered']) ? (bool)$data['auto_triggered'] : false;

if (!in_array($status, ['draft', 'open'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Status must be draft or open when creating an RFQ']);
    exit;
}

if ($quantity <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Quantity must be greater than 0']);
    exit;
}

try {
    // 1. Verify component exists
    $componentStmt = $pdo->prepare("
        SELECT id, name, sku, stock_qty, low_stock_threshold
        FROM components
        WHERE id = :id AND is_active = TRUE
        LIMIT 1
    ");
    $componentStmt->execute(['id' => $componentId]);
    $component = $componentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$component) {
        http_response_code(404);
        echo json_encode(['error' => 'Component not found']);
        exit;
    }

    // 2. Require explicit supplier USER id
    $supplierId = isset($data['supplier_id']) ? (int)$data['supplier_id'] : null;

    if ($supplierId === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'supplier_id is required for RFQ creation'
        ]);
        exit;
    }

    // 3. Verify supplier exists as supplier user
    $supplierUserStmt = $pdo->prepare("
        SELECT id, name, email, role
        FROM users
        WHERE id = :id
          AND role = 'fournisseur'
          AND is_active = TRUE
        LIMIT 1
    ");
    $supplierUserStmt->execute(['id' => $supplierId]);
    $supplierUser = $supplierUserStmt->fetch(PDO::FETCH_ASSOC);

    if (!$supplierUser) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Selected supplier user not found or not active'
        ]);
        exit;
    }

    // 4. Insert RFQ request
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
            :status,
            :client_message,
            :deadline_date,
            1,
            FALSE,
            :auto_triggered
        )
    ");

    $insertStmt->execute([
        'client_id' => $_SESSION['user_id'],
        'component_id' => $componentId,
        'supplier_id' => $supplierId,
        'quantity_requested' => $quantity,
        'status' => $status,
        'client_message' => $clientMessage,
        'deadline_date' => $deadlineDate,
        'auto_triggered' => $autoTriggered ? 1 : 0
    ]);

    $rfqId = (int)$pdo->lastInsertId();

    echo json_encode([
        'message' => 'RFQ created successfully',
        'rfq_id' => $rfqId,
        'rfq' => [
            'id' => $rfqId,
            'client_id' => (int)$_SESSION['user_id'],
            'component_id' => $componentId,
            'component_name' => $component['name'],
            'component_sku' => $component['sku'],
            'supplier_id' => $supplierId,
            'supplier_name' => $supplierUser['name'],
            'quantity_requested' => $quantity,
            'status' => $status,
            'deadline_date' => $deadlineDate,
            'auto_triggered' => $autoTriggered,
            'current_stock_qty' => (float)$component['stock_qty']
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to create RFQ',
        'details' => $e->getMessage()
    ]);
}