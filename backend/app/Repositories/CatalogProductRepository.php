<?php

namespace App\Repositories;

use App\Queries\CatalogProductQuery;
use App\Support\ApiResponse;

class CatalogProductRepository extends Repository
{
    public function list(array $filters, int $page = 1, int $limit = 20): array
    {
        $page = max(1, $page);
        $limit = max(1, min($limit, 100));
        $offset = ($page - 1) * $limit;

        $where = CatalogProductQuery::filters($filters);

        $totalRow = $this
            ->query(CatalogProductQuery::count($where['sql']), $where['params'])
            ->fetchOne();

        $total = (int) ($totalRow['total'] ?? 0);

        $rows = $this
            ->query(
                CatalogProductQuery::list($where['sql'], $limit, $offset),
                $where['params']
            )
            ->fetchMany();

        $items = array_map(fn ($row) => $this->formatProduct($row), $rows);

        return ApiResponse::success('Catalog products fetched successfully', [
            'items' => $items,
            'filters' => $this->cleanFilters($filters),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $total > 0 ? (int) ceil($total / $limit) : 0,
            ],
        ]);
    }

    public function findById(int $id): ?array
    {
        $row = $this
            ->query(CatalogProductQuery::findById(), [$id])
            ->fetchOne();

        return $row ? $this->formatProduct($row) : null;
    }

    private function formatProduct(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'sku' => $row['sku'],
            'description' => $row['description'],
            'category' => $row['category'],
            'unit_of_measure' => $row['unit_of_measure'],
            'availability_status' => $row['availability_status'],
            'is_active' => (bool) $row['is_active'],
        ];
    }

    private function cleanFilters(array $filters): array
    {
        return [
            'search' => $filters['search'] ?? null,
            'category' => $filters['category'] ?? null,
            'availability' => $filters['availability'] ?? null,
        ];
    }
}