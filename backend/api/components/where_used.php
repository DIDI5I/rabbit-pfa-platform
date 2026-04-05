<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid component id']);
    exit;
}

$componentId = (int) $_GET['id'];

$sql = "
WITH RECURSIVE where_used AS (
    SELECT
        c.id,
        c.name,
        c.sku,
        c.category,
        0 AS depth,
        CAST(c.id AS CHAR(1000)) AS path
    FROM components c
    WHERE c.id = :component_id

    UNION ALL

    SELECT
        c.id,
        c.name,
        c.sku,
        c.category,
        wu.depth + 1 AS depth,
        CONCAT(wu.path, ',', c.id) AS path
    FROM dependencies d
    JOIN components c ON d.parent_id = c.id
    JOIN where_used wu ON d.child_id = wu.id
    WHERE FIND_IN_SET(c.id, wu.path) = 0
      AND wu.depth < 10
)
SELECT
    id,
    name,
    sku,
    category,
    depth AS levels_above
FROM where_used
WHERE depth > 0
ORDER BY depth, name
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['component_id' => $componentId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Query failed',
        'details' => $e->getMessage()
    ]);
}