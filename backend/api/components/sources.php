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
SELECT
    s.id AS supplier_id,
    s.name AS supplier,
    s.country,
    ps.supplier_part_num,
    ps.unit_cost,
    ps.lead_time_days,
    ps.min_order_qty,
    ps.is_preferred,
    ps.valid_from,
    ps.valid_to
FROM part_sources ps
JOIN suppliers s ON s.id = ps.supplier_id
WHERE ps.component_id = :component_id
ORDER BY ps.is_preferred DESC, ps.unit_cost ASC
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