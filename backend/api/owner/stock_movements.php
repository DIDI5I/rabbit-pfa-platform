<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/role_check.php';
requireRole('owner');

require_once __DIR__ . '/../../includes/db.php';

$sql = "
SELECT
    sm.id,
    sm.component_id,
    c.name AS component_name,
    c.sku,
    sm.type,
    sm.quantity,
    sm.reason,
    sm.created_at,
    u.name AS user_name
FROM stock_movements sm
JOIN components c ON c.id = sm.component_id
LEFT JOIN users u ON u.id = sm.created_by
ORDER BY sm.created_at DESC
LIMIT 100
";

try {
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['quantity'] = (float)$row['quantity'];
    }

    echo json_encode([
        'count' => count($rows),
        'movements' => $rows
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch movements',
        'details' => $e->getMessage()
    ]);
}