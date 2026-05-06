<?php

namespace App\Repositories;

use App\Queries\CatalogProductRelationQuery;
use App\Support\ApiResponse;

class CatalogProductRelationRepository extends Repository
{
    public function productExists(int $productId): bool
    {
        return $this
            ->query(CatalogProductRelationQuery::productExists(), [$productId])
            ->exists();
    }

    public function list(int $productId): array
    {
        $rows = $this
            ->query(CatalogProductRelationQuery::list(), [$productId])
            ->fetchMany();

        $items = array_map(
            fn ($row) => $this->formatRelation($row),
            $rows
        );

        return ApiResponse::success('Catalog product relations fetched successfully', [
            'product_id' => $productId,
            'items' => $items,
            'summary' => [
                'total' => count($items),
                'by_relation_type' => $this->countByRelationType($items),
            ],
        ]);
    }

    private function formatRelation(array $row): array
    {
        return [
            'relation_id' => (int) $row['relation_id'],
            'relation_type' => $row['relation_type'],
            'quantity' => isset($row['quantity']) ? (float) $row['quantity'] : null,
            'notes' => $row['notes'] ?? null,

            'product' => [
                'id' => (int) $row['product_id'],
                'name' => $row['name'],
                'sku' => $row['sku'],
                'description' => $row['description'],
                'category' => $row['category'],
                'unit_of_measure' => $row['unit_of_measure'],
                'availability_status' => $row['availability_status'],
                'is_active' => (bool) $row['is_active'],
            ],
        ];
    }

    private function countByRelationType(array $items): array
    {
        $counts = [];

        foreach ($items as $item) {
            $type = $item['relation_type'];

            if (!isset($counts[$type])) {
                $counts[$type] = 0;
            }

            $counts[$type]++;
        }

        ksort($counts);

        return $counts;
    }
}