<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$rfqId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($rfqId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid RFQ id']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];
$supplierCompanyId = $_SESSION['supplier_company_id'] ?? null;

try {
    // 1. Get RFQ main record
    $stmt = $pdo->prepare("
        SELECT
            rfq.*,
            c.name AS component_name,
            c.sku,
            c.stock_qty,
            c.low_stock_threshold,
            cu.name AS client_name,
            cu.email AS client_email,
            s.name AS supplier_name
        FROM rfq_requests rfq
        JOIN components c ON c.id = rfq.component_id
        LEFT JOIN users cu ON cu.id = rfq.client_id
        LEFT JOIN suppliers s ON s.id = rfq.supplier_id
        WHERE rfq.id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $rfqId]);
    $rfq = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rfq) {
        http_response_code(404);
        echo json_encode(['error' => 'RFQ not found']);
        exit;
    }

    // 2. Access control
    if ($role === 'client') {
        if ((int)$rfq['client_id'] !== $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Not authorized to view this RFQ']);
            exit;
        }
    } elseif ($role === 'fournisseur') {
        if ($supplierCompanyId === null) {
            http_response_code(403);
            echo json_encode(['error' => 'Supplier account is not linked to a supplier company']);
            exit;
        }

        if ((int)$rfq['supplier_id'] !== (int)$supplierCompanyId) {
            http_response_code(403);
            echo json_encode(['error' => 'Not authorized to view this RFQ']);
            exit;
        }
    } elseif ($role !== 'owner') {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid role']);
        exit;
    }

    // 3. Get revision history
    $revStmt = $pdo->prepare("
        SELECT
            id,
            rfq_id,
            revision_id,
            supplier_id,
            quoted_price,
            lead_time_days,
            supplier_note,
            created_at
        FROM rfq_revisions
        WHERE rfq_id = :rfq_id
        ORDER BY revision_id ASC, created_at ASC
    ");
    $revStmt->execute(['rfq_id' => $rfqId]);
    $revisions = $revStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Normalize RFQ types
    $rfq['id'] = (int)$rfq['id'];
    $rfq['client_id'] = isset($rfq['client_id']) ? (int)$rfq['client_id'] : null;
    $rfq['component_id'] = (int)$rfq['component_id'];
    $rfq['supplier_id'] = isset($rfq['supplier_id']) ? (int)$rfq['supplier_id'] : null;
    $rfq['quantity_requested'] = isset($rfq['quantity_requested']) ? (float)$rfq['quantity_requested'] : null;
    $rfq['quoted_price'] = isset($rfq['quoted_price']) ? (float)$rfq['quoted_price'] : null;
    $rfq['revision_id'] = isset($rfq['revision_id']) ? (int)$rfq['revision_id'] : 0;
    $rfq['is_blind'] = isset($rfq['is_blind']) ? (bool)$rfq['is_blind'] : false;
    $rfq['auto_triggered'] = isset($rfq['auto_triggered']) ? (bool)$rfq['auto_triggered'] : false;
    $rfq['stock_qty'] = isset($rfq['stock_qty']) ? (float)$rfq['stock_qty'] : null;
    $rfq['low_stock_threshold'] = isset($rfq['low_stock_threshold']) ? (float)$rfq['low_stock_threshold'] : null;

    // 5. Normalize revisions
    foreach ($revisions as &$rev) {
        $rev['id'] = (int)$rev['id'];
        $rev['rfq_id'] = (int)$rev['rfq_id'];
        $rev['revision_id'] = (int)$rev['revision_id'];
        $rev['supplier_id'] = isset($rev['supplier_id']) ? (int)$rev['supplier_id'] : null;
        $rev['quoted_price'] = isset($rev['quoted_price']) ? (float)$rev['quoted_price'] : null;
        $rev['lead_time_days'] = isset($rev['lead_time_days']) ? (int)$rev['lead_time_days'] : null;
    }
    unset($rev);

    echo json_encode([
        'rfq' => $rfq,
        'revisions' => $revisions,
        'revision_count' => count($revisions)
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch RFQ details',
        'details' => $e->getMessage()
    ]);
}