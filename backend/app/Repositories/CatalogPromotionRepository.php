<?php

namespace App\Repositories;

use App\Queries\CatalogPromotionQuery;
use App\Support\ApiResponse;

class CatalogPromotionRepository extends Repository
{
    public function activeList(int $page = 1, int $limit = 20): array
    {
        $page = max(1, $page);
        $limit = max(1, min($limit, 100));
        $offset = ($page - 1) * $limit;

        $totalRow = $this
            ->query(CatalogPromotionQuery::activeCount())
            ->fetchOne();

        $total = (int) ($totalRow['total'] ?? 0);

        $rows = $this
            ->query(CatalogPromotionQuery::activeList($limit, $offset))
            ->fetchMany();

        return ApiResponse::success('Active catalog promotions fetched successfully', [
            'items' => array_map(fn ($row) => $this->formatPromotion($row), $rows),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $total > 0 ? (int) ceil($total / $limit) : 0,
            ],
        ]);
    }

    public function productExists(int $productId): bool
    {
        return $this
            ->query(CatalogPromotionQuery::productExists(), [$productId])
            ->exists();
    }

    public function activeForProduct(int $productId): array
    {
        $rows = $this
            ->query(CatalogPromotionQuery::productActivePromotions(), [$productId])
            ->fetchMany();

        return ApiResponse::success('Catalog product promotions fetched successfully', [
            'product_id' => $productId,
            'items' => array_map(fn ($row) => $this->formatPromotion($row), $rows),
            'summary' => [
                'total' => count($rows),
            ],
        ]);
    }

    private function formatPromotion(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'discount_type' => $row['discount_type'],
            'discount_value' => (float) $row['discount_value'],
            'starts_at' => $row['starts_at'],
            'ends_at' => $row['ends_at'],
            'status' => $row['status'],
        ];
    }
}