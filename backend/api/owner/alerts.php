<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/role_check.php';
requireRole('owner');

require_once __DIR__ . '/../../includes/db.php';

$sql = "
SELECT
    id,
    name,
    sku,
    category,
    unit_of_measure,
    stock_qty,
    low_stock_threshold,
    specs
FROM components
WHERE stock_qty < low_stock_threshold
  AND is_active = TRUE
ORDER BY stock_qty ASC, name ASC
";

try {
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        if (isset($row['specs']) && is_string($row['specs'])) {
            $decoded = json_decode($row['specs'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $row['specs'] = $decoded;
            }
        }

        $row['stock_qty'] = (float)$row['stock_qty'];
        $row['low_stock_threshold'] = (float)$row['low_stock_threshold'];
    }

    echo json_encode([
        'count' => count($rows),
        'alerts' => $rows
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch alerts',
        'details' => $e->getMessage()
    ]);
}