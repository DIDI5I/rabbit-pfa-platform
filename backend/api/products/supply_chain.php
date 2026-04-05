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
WITH RECURSIVE supply_chain AS (
    SELECT
        c.id,
        c.name,
        c.sku,
        c.category,
        c.stock_qty,
        c.specs,
        CAST(1.0 AS DECIMAL(18,4)) AS extended_qty,
        0 AS depth,
        CAST(c.id AS CHAR(1000)) AS path
    FROM components c
    WHERE c.id = :product_id

    UNION ALL

    SELECT
        c.id,
        c.name,
        c.sku,
        c.category,
        c.stock_qty,
        c.specs,
        CAST(sc.extended_qty * d.qty_required AS DECIMAL(18,4)) AS extended_qty,
        sc.depth + 1 AS depth,
        CONCAT(sc.path, ',', c.id) AS path
    FROM dependencies d
    JOIN components c ON d.child_id = c.id
    JOIN supply_chain sc ON d.parent_id = sc.id
    WHERE FIND_IN_SET(c.id, sc.path) = 0
      AND sc.depth < 10
)
SELECT
    sc.id,
    sc.name,
    sc.sku,
    sc.category,
    sc.stock_qty,
    sc.specs,
    sc.extended_qty,
    sc.depth AS tier_level,
    sc.path,
    ps.unit_cost,
    ps.lead_time_days,
    CAST(sc.extended_qty * ps.unit_cost AS DECIMAL(18,4)) AS total_cost,
    sup.name AS preferred_supplier
FROM supply_chain sc
LEFT JOIN part_sources ps
    ON ps.component_id = sc.id
   AND ps.is_preferred = TRUE
LEFT JOIN suppliers sup
    ON ps.supplier_id = sup.id
ORDER BY sc.path
";

function buildTree(array $rows): ?array
{
    $nodes = [];
    $root = null;

    foreach ($rows as $row) {
        $key = $row['id'] . '-' . $row['path'];

        $specs = $row['specs'];
        if (is_string($specs)) {
            $decoded = json_decode($specs, true);
            $specs = json_last_error() === JSON_ERROR_NONE ? $decoded : $specs;
        }

        $nodes[$key] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'sku' => $row['sku'],
            'category' => $row['category'],
            'stock_qty' => (float) $row['stock_qty'],
            'specs' => $specs,
            'extended_qty' => (float) $row['extended_qty'],
            'tier_level' => (int) $row['tier_level'],
            'total_cost' => $row['total_cost'] !== null ? (float) $row['total_cost'] : null,
            'preferred_supplier' => $row['preferred_supplier'] !== null ? [
                'name' => $row['preferred_supplier'],
                'lead_time_days' => $row['lead_time_days'] !== null ? (int) $row['lead_time_days'] : null,
                'unit_cost' => $row['unit_cost'] !== null ? (float) $row['unit_cost'] : null
            ] : null,
            'children' => [],
            '_path' => $row['path']
        ];
    }

    foreach ($rows as $row) {
        $currentKey = $row['id'] . '-' . $row['path'];

        if ((int) $row['tier_level'] === 0) {
            $root = &$nodes[$currentKey];
            continue;
        }

        $parts = explode(',', $row['path']);
        $parentId = $parts[count($parts) - 2];
        $parentPath = implode(',', array_slice($parts, 0, -1));
        $parentKey = $parentId . '-' . $parentPath;

        if (isset($nodes[$parentKey])) {
            $nodes[$parentKey]['children'][] = &$nodes[$currentKey];
        }
    }

    if ($root === null) {
        return null;
    }

    removeInternalPath($root);
    return $root;
}

function removeInternalPath(array &$node): void
{
    unset($node['_path']);

    foreach ($node['children'] as &$child) {
        removeInternalPath($child);
    }
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['product_id' => $productId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    $tree = buildTree($rows);

    echo json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Query failed',
        'details' => $e->getMessage()
    ]);
}