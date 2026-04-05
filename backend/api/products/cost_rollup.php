<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid product id']);
    exit;
}

$productId = (int) $_GET['id'];

$sql = "
WITH RECURSIVE bom AS (
    SELECT
        c.id,
        CAST(1.0 AS DECIMAL(18,4)) AS extended_qty,
        0 AS depth,
        CAST(c.id AS CHAR(500)) AS path
    FROM components c
    WHERE c.id = :product_id

    UNION ALL

    SELECT
        c.id,
        CAST(bom.extended_qty * d.qty_required AS DECIMAL(18,4)) AS extended_qty,
        bom.depth + 1,
        CONCAT(bom.path, ',', c.id)
    FROM dependencies d
    JOIN components c ON d.child_id = c.id
    JOIN bom ON d.parent_id = bom.id
    WHERE FIND_IN_SET(c.id, bom.path) = 0
      AND bom.depth < 10
      AND d.is_phantom = FALSE
)
SELECT
    COALESCE(SUM(b.extended_qty * ps.unit_cost), 0) AS total_material_cost,
    COUNT(DISTINCT b.id) AS unique_components
FROM bom b
JOIN part_sources ps
    ON ps.component_id = b.id
   AND ps.is_preferred = TRUE
WHERE b.depth > 0
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['product_id' => $productId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    echo json_encode([
        'product_id' => $productId,
        'total_material_cost' => (float) $row['total_material_cost'],
        'unique_components' => (int) $row['unique_components']
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Query failed',
        'details' => $e->getMessage()
    ]);
}