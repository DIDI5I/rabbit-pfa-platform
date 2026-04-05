<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/role_check.php';
requireRole('owner');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/rfq_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['component_id'], $data['type'], $data['quantity'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$componentId = (int)$data['component_id'];
$type = $data['type']; // 'in', 'out', 'adjustment'
$quantity = (float)$data['quantity'];
$reason = $data['reason'] ?? null;

if (!in_array($type, ['in', 'out', 'adjustment'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid movement type']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 🔹 Get current stock
    $stmt = $pdo->prepare("SELECT stock_qty FROM components WHERE id = :id");
    $stmt->execute(['id' => $componentId]);
    $component = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$component) {
        throw new Exception("Component not found");
    }

    $currentStock = (float)$component['stock_qty'];

    // 🔹 Calculate new stock
    if ($type === 'in') {
        $newStock = $currentStock + $quantity;
    } elseif ($type === 'out') {
        $newStock = $currentStock - $quantity;
        if ($newStock < 0) {
            throw new Exception("Stock cannot be negative");
        }
    } else { // adjustment
        $newStock = $quantity;
    }

    // 🔹 Update stock
    $updateStmt = $pdo->prepare("
        UPDATE components
        SET stock_qty = :stock
        WHERE id = :id
    ");
    $updateStmt->execute([
        'stock' => $newStock,
        'id' => $componentId
    ]);

    $autoRfqId = createAutoDraftRfq($pdo, $componentId, (int)$_SESSION['user_id']);

    // 🔹 Insert movement log
    $logStmt = $pdo->prepare("
        INSERT INTO stock_movements
        (component_id, type, quantity, reason, created_by)
        VALUES
        (:component_id, :type, :quantity, :reason, :user_id)
    ");

    $logStmt->execute([
        'component_id' => $componentId,
        'type' => $type,
        'quantity' => $quantity,
        'reason' => $reason,
        'user_id' => $_SESSION['user_id']
    ]);


    $pdo->commit();

    echo json_encode([
        'message' => 'Stock updated',
        'previous_stock' => $currentStock,
        'new_stock' => $newStock,
        'auto_rfq_created' => $autoRfqId !== null,
        'auto_rfq_id' => $autoRfqId
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    $pdo->rollBack();

    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}