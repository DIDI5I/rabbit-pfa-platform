<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/rfq_helper.php';

$userId = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];
$supplierCompanyId = $_SESSION['supplier_company_id'] ?? null;

try {

    if ($role === 'client') {

        $stmt = $pdo->prepare("
            SELECT
                rfq.*,
                c.name AS component_name,
                c.sku,
                s.name AS supplier_name,
                du.name AS decided_by_name
            FROM rfq_requests rfq
            JOIN components c ON c.id = rfq.component_id
            LEFT JOIN suppliers s ON s.id = rfq.supplier_id
            LEFT JOIN users du ON du.id = rfq.decided_by
            WHERE rfq.client_id = :id
            ORDER BY rfq.created_at DESC
        ");

        $stmt->execute(['id' => $userId]);

    } elseif ($role === 'fournisseur') {

        if ($supplierCompanyId === null) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Supplier account is not linked to a supplier company'
            ]);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT
                rfq.*,
                c.name AS component_name,
                c.sku,              
                u.name AS client_name,
                du.name AS decided_by_name
            FROM rfq_requests rfq
            JOIN components c ON c.id = rfq.component_id
            LEFT JOIN users u ON u.id = rfq.client_id
            LEFT JOIN users du ON du.id = rfq.decided_by
            WHERE rfq.supplier_id = :supplier_company_id
            ORDER BY rfq.created_at DESC
        ");

        $stmt->execute(['supplier_company_id' => (int)$supplierCompanyId]);

    } else {
        // owner

        $stmt = $pdo->query("
            SELECT
                rfq.*,
                c.name AS component_name,
                c.sku,
                cu.name AS client_name,
                s.name AS supplier_name,
                du.name AS decided_by_name
            FROM rfq_requests rfq
            JOIN components c ON c.id = rfq.component_id
            LEFT JOIN users cu ON cu.id = rfq.client_id
            LEFT JOIN suppliers s ON s.id = rfq.supplier_id
            LEFT JOIN users du ON du.id = rfq.decided_by
            ORDER BY rfq.created_at DESC
        ");
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['id'] = (int)$row['id'];
        $row['client_id'] = isset($row['client_id']) ? (int)$row['client_id'] : null;
        $row['component_id'] = (int)$row['component_id'];
        $row['supplier_id'] = isset($row['supplier_id']) ? (int)$row['supplier_id'] : null;
        $row['quantity_requested'] = (float)$row['quantity_requested'];
        $row['quoted_price'] = isset($row['quoted_price']) ? (float)$row['quoted_price'] : null;
        $row['revision_id'] = isset($row['revision_id']) ? (int)$row['revision_id'] : 0;
        $row['is_blind'] = isset($row['is_blind']) ? (bool)$row['is_blind'] : false;
        $row['auto_triggered'] = isset($row['auto_triggered']) ? (bool)$row['auto_triggered'] : false;
        $row['allowed_actions'] = getAllowedRfqActions($role, $row);
        $row['decided_by'] = isset($row['decided_by']) ? (int)$row['decided_by'] : null;
}
unset($row);

    echo json_encode([
        'count' => count($rows),
        'role' => $role,
        'rfqs' => $rows
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch RFQs',
        'details' => $e->getMessage()
    ]);
}