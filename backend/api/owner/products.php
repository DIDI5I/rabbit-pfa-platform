<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/role_check.php';
requireRole('owner');

require_once __DIR__ . '/../../includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ======================
    // GET — LIST PRODUCTS
    // ======================
    case 'GET':

        $stmt = $pdo->query("
            SELECT * FROM components
            ORDER BY created_at DESC
        ");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            if (is_string($row['specs'])) {
                $decoded = json_decode($row['specs'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row['specs'] = $decoded;
                }
            }

            $row['stock_qty'] = (float)$row['stock_qty'];
            $row['low_stock_threshold'] = (float)$row['low_stock_threshold'];
            $row['is_active'] = (bool)$row['is_active'];
        }

        echo json_encode([
            'count' => count($rows),
            'products' => $rows
        ], JSON_PRETTY_PRINT);
        break;

    // ======================
    // POST — CREATE PRODUCT
    // ======================
    case 'POST':

        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            exit;
        }

        $sql = "
        INSERT INTO components
        (name, sku, category, unit_of_measure, stock_qty, low_stock_threshold, specs)
        VALUES
        (:name, :sku, :category, :uom, :stock, :threshold, :specs)
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'name' => $data['name'],
            'sku' => $data['sku'],
            'category' => $data['category'],
            'uom' => $data['unit_of_measure'] ?? 'pcs',
            'stock' => $data['stock_qty'] ?? 0,
            'threshold' => $data['low_stock_threshold'] ?? 10,
            'specs' => json_encode($data['specs'] ?? [])
        ]);

        echo json_encode([
            'message' => 'Product created',
            'id' => $pdo->lastInsertId()
        ]);
        break;

    // ======================
    // PUT — UPDATE PRODUCT
    // ======================
    case 'PUT':

        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data || !isset($data['id'], $data['name'], $data['sku'], $data['category'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }

        $checkStmt = $pdo->prepare("
            SELECT id
            FROM components
            WHERE sku = :sku
              AND id != :id
            LIMIT 1
        ");
        
        $checkStmt->execute([
            'sku' => $data['sku'],
            'id' => $data['id']
        ]);

        if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
            http_response_code(409);
            echo json_encode([
                'error' => 'SKU already exists for another product'
            ]);
            exit;
        }

        $sql = "
            UPDATE components
            SET
                name = :name,
                sku = :sku,
                category = :category,
                stock_qty = :stock,
                low_stock_threshold = :threshold,
                specs = :specs
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'id' => $data['id'],
            'name' => $data['name'],
            'sku' => $data['sku'],
            'category' => $data['category'],
            'stock' => $data['stock_qty'] ?? 0,
            'threshold' => $data['low_stock_threshold'] ?? 10,
            'specs' => json_encode($data['specs'] ?? [])
        ]);

        echo json_encode(['message' => 'Product updated']);
        break;

    // ======================
    // DELETE — DEACTIVATE
    // ======================
    case 'DELETE':

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Product ID required']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE components
            SET is_active = FALSE
            WHERE id = :id
        ");

        $stmt->execute(['id' => $data['id']]);

        echo json_encode(['message' => 'Product deactivated']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}