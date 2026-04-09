<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';

function sendJson(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function parseBool(?string $value): ?bool
{
    if ($value === null) {
        return null;
    }

    $value = strtolower(trim($value));

    if (in_array($value, ['1', 'true', 'yes'], true)) {
        return true;
    }

    if (in_array($value, ['0', 'false', 'no'], true)) {
        return false;
    }

    return null;
}

function normalizeSpecs(mixed $specs): mixed
{
    if ($specs === null) {
        return null;
    }

    if (!is_string($specs)) {
        return $specs;
    }

    $decoded = json_decode($specs, true);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : $specs;
}

function normalizeMatchedSource(mixed $matchedSource): ?array
{
    if ($matchedSource === null) {
        return null;
    }

    if (is_array($matchedSource)) {
        return $matchedSource;
    }

    if (!is_string($matchedSource)) {
        return null;
    }

    $decoded = json_decode($matchedSource, true);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
}

try {
    $allowedCategories = ['assembly', 'sub_assembly', 'component', 'raw_material'];

    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = (int) ($_GET['limit'] ?? 10);
    $limit = max(1, min($limit, 100));
    $offset = ($page - 1) * $limit;

    $search = trim((string) ($_GET['search'] ?? ''));
    $category = isset($_GET['category']) ? trim((string) $_GET['category']) : null;
    $lowStock = parseBool($_GET['low_stock'] ?? null);

    $supplierId = isset($_GET['supplier_id']) && is_numeric($_GET['supplier_id'])
        ? (int) $_GET['supplier_id']
        : null;

    $supplierCountry = trim((string) ($_GET['supplier_country'] ?? ''));
    $supplierCountry = $supplierCountry !== '' ? $supplierCountry : null;

    $supplierName = trim((string) ($_GET['supplier_name'] ?? ''));
    $supplierName = $supplierName !== '' ? $supplierName : null;

    $minSupplierRating = isset($_GET['min_supplier_rating']) && is_numeric($_GET['min_supplier_rating'])
        ? (float) $_GET['min_supplier_rating']
        : null;

    $minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price'])
        ? (float) $_GET['min_price']
        : null;

    $maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price'])
        ? (float) $_GET['max_price']
        : null;

    $activeOnly = parseBool($_GET['active_only'] ?? null);
    if ($activeOnly === null) {
        $activeOnly = true;
    }

    if ($category !== null && $category !== '' && !in_array($category, $allowedCategories, true)) {
        sendJson(400, ['error' => 'Invalid category']);
    }

    if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
        sendJson(400, ['error' => 'min_price cannot be greater than max_price']);
    }

    if ($minSupplierRating !== null && ($minSupplierRating < 0 || $minSupplierRating > 5)) {
        sendJson(400, ['error' => 'min_supplier_rating must be between 0 and 5']);
    }

    $where = [];
    $params = [];

    if ($activeOnly) {
        $where[] = 'c.is_active = 1';
    }

    if ($search !== '') {
        $where[] = '(c.name LIKE :search OR c.sku LIKE :search OR c.description LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    if ($category !== null && $category !== '') {
        $where[] = 'c.category = :category';
        $params['category'] = $category;
    }

    if ($lowStock === true) {
        $where[] = 'c.stock_qty <= c.low_stock_threshold';
    } elseif ($lowStock === false) {
        $where[] = 'c.stock_qty > c.low_stock_threshold';
    }

    $needsSourceFilter =
        $supplierId !== null ||
        $supplierCountry !== null ||
        $supplierName !== null ||
        $minSupplierRating !== null ||
        $minPrice !== null ||
        $maxPrice !== null;

    if ($needsSourceFilter) {
        $sourceConditions = [
            'ps.component_id = c.id',
            's.is_active = 1',
            '(ps.valid_from IS NULL OR ps.valid_from <= CURRENT_DATE())',
            '(ps.valid_to IS NULL OR ps.valid_to >= CURRENT_DATE())'
        ];

        if ($supplierId !== null) {
            $sourceConditions[] = 'ps.supplier_id = :supplier_id';
            $params['supplier_id'] = $supplierId;
        }

        if ($supplierCountry !== null) {
            $sourceConditions[] = 's.country = :supplier_country';
            $params['supplier_country'] = $supplierCountry;
        }

        if ($supplierName !== null) {
            $sourceConditions[] = 's.name LIKE :supplier_name';
            $params['supplier_name'] = '%' . $supplierName . '%';
        }

        if ($minSupplierRating !== null) {
            $sourceConditions[] = 's.rating >= :min_supplier_rating';
            $params['min_supplier_rating'] = $minSupplierRating;
        }

        if ($minPrice !== null) {
            $sourceConditions[] = 'ps.unit_cost >= :min_price';
            $params['min_price'] = $minPrice;
        }

        if ($maxPrice !== null) {
            $sourceConditions[] = 'ps.unit_cost <= :max_price';
            $params['max_price'] = $maxPrice;
        }

        $where[] = "EXISTS (
            SELECT 1
            FROM part_sources ps
            INNER JOIN suppliers s ON s.id = ps.supplier_id
            WHERE " . implode(' AND ', $sourceConditions) . '
        )';
    }

    $whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "
        SELECT COUNT(*)
        FROM components c
        $whereSql
    ";

    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue(':' . $key, $value);
    }
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $matchedSourceConditions = [
        'ps.component_id = c.id',
        's.is_active = 1',
        '(ps.valid_from IS NULL OR ps.valid_from <= CURRENT_DATE())',
        '(ps.valid_to IS NULL OR ps.valid_to >= CURRENT_DATE())'
    ];

    if ($supplierId !== null) {
        $matchedSourceConditions[] = 'ps.supplier_id = :matched_supplier_id';
    }

    if ($supplierCountry !== null) {
        $matchedSourceConditions[] = 's.country = :matched_supplier_country';
    }

    if ($supplierName !== null) {
        $matchedSourceConditions[] = 's.name LIKE :matched_supplier_name';
    }

    if ($minSupplierRating !== null) {
        $matchedSourceConditions[] = 's.rating >= :matched_min_supplier_rating';
    }

    if ($minPrice !== null) {
        $matchedSourceConditions[] = 'ps.unit_cost >= :matched_min_price';
    }

    if ($maxPrice !== null) {
        $matchedSourceConditions[] = 'ps.unit_cost <= :matched_max_price';
    }

    $matchedSourceWhereSql = implode(' AND ', $matchedSourceConditions);

    $dataSql = "
        SELECT
            c.id,
            c.name,
            c.sku,
            c.category,
            c.unit_of_measure,
            c.stock_qty,
            c.low_stock_threshold,
            CASE
                WHEN c.stock_qty <= 0 THEN 'out'
                WHEN c.stock_qty <= c.low_stock_threshold THEN 'low'
                ELSE 'ok'
            END AS stock_status,
            EXISTS(
                SELECT 1
                FROM dependencies d
                WHERE d.parent_id = c.id
            ) AS has_children,
            CASE
                WHEN EXISTS (SELECT 1 FROM dependencies d WHERE d.parent_id = c.id)
                     AND EXISTS (SELECT 1 FROM dependencies d2 WHERE d2.child_id = c.id)
                    THEN 'subassembly'
                WHEN EXISTS (SELECT 1 FROM dependencies d WHERE d.parent_id = c.id)
                    THEN 'top_level_assembly'
                ELSE 'leaf'
            END AS node_role,
            (
                SELECT JSON_OBJECT(
                    'supplier_name', s.name,
                    'unit_cost', ps.unit_cost,
                    'lead_time_days', ps.lead_time_days
                )
                FROM part_sources ps
                INNER JOIN suppliers s ON s.id = ps.supplier_id
                WHERE $matchedSourceWhereSql
                ORDER BY
                    ps.is_preferred DESC,
                    ps.unit_cost ASC,
                    s.rating DESC,
                    s.name ASC
                LIMIT 1
            ) AS matched_source
        FROM components c
        $whereSql
        ORDER BY c.created_at DESC, c.id DESC
        LIMIT :limit OFFSET :offset
    ";
        $dataStmt = $pdo->prepare($dataSql);

    foreach ($params as $key => $value) {
        $dataStmt->bindValue(':' . $key, $value);
    }

    if ($supplierId !== null) {
        $dataStmt->bindValue(':matched_supplier_id', $supplierId, PDO::PARAM_INT);
    }

    if ($supplierCountry !== null) {
        $dataStmt->bindValue(':matched_supplier_country', $supplierCountry);
    }

    if ($supplierName !== null) {
        $dataStmt->bindValue(':matched_supplier_name', '%' . $supplierName . '%');
    }

    if ($minSupplierRating !== null) {
        $dataStmt->bindValue(':matched_min_supplier_rating', $minSupplierRating);
    }

    if ($minPrice !== null) {
        $dataStmt->bindValue(':matched_min_price', $minPrice);
    }

    if ($maxPrice !== null) {
        $dataStmt->bindValue(':matched_max_price', $maxPrice);
    }

    $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $dataStmt->execute();
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
    $row['stock_qty'] = (float) $row['stock_qty'];
    $row['low_stock_threshold'] = (float) $row['low_stock_threshold'];
    $row['has_children'] = (bool) $row['has_children'];
    $row['matched_source'] = normalizeMatchedSource($row['matched_source']);
    }
    unset($row);

    sendJson(200, [
        'data' => $rows,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => $total > 0 ? (int) ceil($total / $limit) : 0
        ],
        'filters' => [
            'search' => $search !== '' ? $search : null,
            'category' => $category,
            'low_stock' => $lowStock,
            'supplier_id' => $supplierId,
            'supplier_name' => $supplierName,
            'supplier_country' => $supplierCountry,
            'min_supplier_rating' => $minSupplierRating,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'active_only' => $activeOnly
        ]
    ]);
} catch (PDOException $e) {
    sendJson(500, [
        'error' => 'Failed to fetch products',
        'details' => $e->getMessage()
    ]);
}