<?php

namespace App\Repositories;

use App\Queries\PromotionProductQuery;
use App\Support\ApiResponse;

class PromotionProductRepository extends Repository
{
    public function promotionExists(int $promotionId): bool
    {
        return $this
            ->query(PromotionProductQuery::promotionExists(), [$promotionId])
            ->exists();
    }

    public function componentExists(int $componentId): bool
    {
        return $this
            ->query(PromotionProductQuery::componentExists(), [$componentId])
            ->exists();
    }

    public function list(int $promotionId): array
    {
        $rows = $this
            ->query(PromotionProductQuery::list(), [$promotionId])
            ->fetchMany();

        $items = array_map(
            fn ($row) => $this->formatPromotionProduct($row),
            $rows
        );

        return ApiResponse::success('Promotion products fetched successfully', [
            'promotion_id' => $promotionId,
            'items' => $items,
            'summary' => [
                'total' => count($items),
                'by_availability' => $this->countBy($items, 'availability_status'),
                'by_category' => $this->countByNestedProduct($items, 'category'),
            ],
        ]);
    }

    public function attach(int $promotionId, int $componentId): array
    {
        $this->query(
            PromotionProductQuery::attach(),
            [$promotionId, $componentId]
        );

        return $this->list($promotionId);
    }

    public function detach(int $promotionId, int $componentId): array
    {
        $this->query(
            PromotionProductQuery::detach(),
            [$promotionId, $componentId]
        );

        return $this->list($promotionId);
    }

    private function formatPromotionProduct(array $row): array
    {
        return [
            'promotion_product_id' => (int) $row['promotion_product_id'],
            'promotion_id' => (int) $row['promotion_id'],
            'component_id' => (int) $row['component_id'],
            'created_at' => $row['created_at'],

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

    private function countBy(array $items, string $field): array
    {
        $counts = [];

        foreach ($items as $item) {
            $value = $item['product'][$field] ?? $item[$field] ?? 'UNKNOWN';

            if (!isset($counts[$value])) {
                $counts[$value] = 0;
            }

            $counts[$value]++;
        }

        ksort($counts);

        return $counts;
    }

    private function countByNestedProduct(array $items, string $field): array
    {
        $counts = [];

        foreach ($items as $item) {
            $value = $item['product'][$field] ?? 'UNKNOWN';

            if (!isset($counts[$value])) {
                $counts[$value] = 0;
            }

            $counts[$value]++;
        }

        ksort($counts);

        return $counts;
    }
}