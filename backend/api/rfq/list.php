<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/db.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {

    if ($role === 'client') {

        $stmt = $pdo->prepare("
            SELECT
                rfq.*,
                c.name AS component_name,
                c.sku,
                u.name AS supplier_name
            FROM rfq_requests rfq
            JOIN components c ON c.id = rfq.component_id
            LEFT JOIN users u ON u.id = rfq.supplier_id
            WHERE rfq.client_id = :id
            ORDER BY rfq.created_at DESC
        ");

        $stmt->execute(['id' => $userId]);

    } elseif ($role === 'fournisseur') {

        $stmt = $pdo->prepare("
            SELECT
                rfq.*,
                c.name AS component_name,
                c.sku,
                u.name AS client_name
            FROM rfq_requests rfq
            JOIN components c ON c.id = rfq.component_id
            LEFT JOIN users u ON u.id = rfq.client_id
            WHERE rfq.supplier_id = :id
            ORDER BY rfq.created_at DESC
        ");

        $stmt->execute(['id' => $userId]);

    } else {
        // owner

        $stmt = $pdo->query("
            SELECT
                rfq.*,
                c.name AS component_name,
                c.sku,
                cu.name AS client_name,
                su.name AS supplier_name
            FROM rfq_requests rfq
            JOIN components c ON c.id = rfq.component_id
            LEFT JOIN users cu ON cu.id = rfq.client_id
            LEFT JOIN users su ON su.id = rfq.supplier_id
            ORDER BY rfq.created_at DESC
        ");
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalize types
    foreach ($rows as &$row) {
        $row['quantity_requested'] = (float)$row['quantity_requested'];
        $row['quoted_price'] = isset($row['quoted_price']) ? (float)$row['quoted_price'] : null;
        $row['revision_id'] = (int)$row['revision_id'];
        $row['is_blind'] = (bool)$row['is_blind'];
        $row['auto_triggered'] = (bool)$row['auto_triggered'];
    }

    echo json_encode([
        'count' => count($rows),
        'role' => $role,
        'rfqs' => $rows
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch RFQs',
        'details' => $e->getMessage()
    ]);
}