<?php

namespace App\Repositories;

use App\Forms\ProductRequest;
use App\Queries\ProductQuery;
use App\Forms\StoreProductRequest;
use App\Forms\UpdateProductRequest;
use App\Support\ApiResponse;


class ProductRepository extends Repository
{
    public function search(ProductRequest $request): array
    {
        $where = ProductQuery::where($request);

        $total = $this->countProducts($where['sql'], $where['params']);

        $products = $this->fetchProducts(
            $where['sql'],
            $where['params'],
            $request->limit(),
            $request->offset()
        );
        return ApiResponse::success('Products fetched successfully', [
            'items' => $products,
            'pagination' => [
                'page' => $request->page(),
                'limit' => $request->limit(),
                'total' => $total,
                'total_pages' => $total > 0
                    ? (int) ceil($total / $request->limit())
                    : 0,
            ],
        ]);
    }

    private function countProducts(string $whereSql, array $params): int
    {
        $row = $this
            ->query("SELECT COUNT(*) AS total FROM components c $whereSql", $params)
            ->fetchOne();

        return (int) ($row['total'] ?? 0);
    }

    private function fetchProducts(
        string $whereSql,
        array $params,
        int $limit,
        int $offset
    ): array {
        $fields = ProductQuery::selectFields();

        $sql = "
            SELECT $fields
            FROM components c
            $whereSql
            ORDER BY c.created_at DESC, c.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $rows = $this->query($sql, $params)->fetchMany();

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['stock_qty'] = (float) $row['stock_qty'];
            $row['low_stock_threshold'] = (float) $row['low_stock_threshold'];
            $row['ved_class'] = $row['ved_class'] ?? null;
            $row['is_active'] = (bool) $row['is_active'];
            $row['has_children'] = (bool) $row['has_children'];

            if ($row['matched_source']) {
        $row['matched_source'] = json_decode($row['matched_source'], true);
    }

        }
        

        unset($row);

        return $rows;
    }
    public function findById(int $id): ?array
    {
        $product = $this
        ->query(ProductQuery::findById(), [$id])
        ->fetchOne();

        if (!$product) {
            return null;
        }

        $product['id'] = (int) $product['id'];
        $product['stock_qty'] = (float) $product['stock_qty'];
        $product['low_stock_threshold'] = (float) $product['low_stock_threshold'];
        $product['is_active'] = (bool) $product['is_active'];
        $product['has_children'] = (bool) $product['has_children'];

        if (isset($product['matched_source']) && $product['matched_source']) {
            $product['matched_source'] = json_decode($product['matched_source'], true);
        } else {
            $product['matched_source'] = null;
        }

        return $product;
    }
    public function create(StoreProductRequest $request): array
    {
        $this->query(
            ProductQuery::insert(),
            [
                $request->name(),
                $request->sku(),
                $request->description(),
                $request->category(),
                $request->unitOfMeasure(),
                $request->stockQty(),
                $request->lowStockThreshold(),
                $request->vedClass(),
            ]
        );

        $id = (int) $this->connection->lastInsertId();

        return $this->findById($id);
    }
    public function update(int $id, UpdateProductRequest $request): ?array
    {
        $data = $request->data();

        if (empty($data)) {
            return $this->findById($id);
        }

        $this->query(
            ProductQuery::update(array_keys($data)),
            [...array_values($data), $id]
        );

        return $this->findById($id);
    }

    public function softDelete(int $id): void
    {
        $this->query(
            ProductQuery::softDelete(),
            [$id]
        );
    }

    public function existsById(int $id): bool
    {
        return $this
            ->query("SELECT id FROM components WHERE id = ? LIMIT 1", [$id])
            ->exists();
    }
}