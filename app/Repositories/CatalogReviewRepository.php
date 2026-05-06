<?php

namespace App\Repositories;

use App\Forms\StoreCatalogReviewRequest;
use App\Queries\CatalogReviewQuery;
use App\Support\ApiResponse;

class CatalogReviewRepository extends Repository
{
    public function productExists(int $productId): bool
    {
        return $this
            ->query(CatalogReviewQuery::productExists(), [$productId])
            ->exists();
    }

    public function create(int $productId, StoreCatalogReviewRequest $request): array
    {
        $this->query(CatalogReviewQuery::insert(), [
            $productId,
            $request->userId(),
            $request->rating(),
            $request->title(),
            $request->comment(),
        ]);

        $id = (int) $this->connection->lastInsertId();

        $review = $this->findById($id);

        return ApiResponse::success('Review submitted successfully and is pending moderation', $review, 201);
    }

    public function approvedList(int $productId, int $page = 1, int $limit = 20): array
    {
        $page = max(1, $page);
        $limit = max(1, min($limit, 100));
        $offset = ($page - 1) * $limit;

        $totalRow = $this
            ->query(CatalogReviewQuery::approvedCount(), [$productId])
            ->fetchOne();

        $total = (int) ($totalRow['total'] ?? 0);

        $rows = $this
            ->query(CatalogReviewQuery::approvedList($limit, $offset), [$productId])
            ->fetchMany();

        return ApiResponse::success('Catalog product reviews fetched successfully', [
            'product_id' => $productId,
            'items' => array_map(fn ($row) => $this->formatReview($row), $rows),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $total > 0 ? (int) ceil($total / $limit) : 0,
            ],
        ]);
    }

    public function ratingSummary(int $productId): array
    {
        $row = $this
            ->query(CatalogReviewQuery::ratingSummary(), [$productId])
            ->fetchOne();

        $reviewCount = (int) ($row['review_count'] ?? 0);

        return ApiResponse::success('Catalog product rating summary fetched successfully', [
            'product_id' => $productId,
            'review_count' => $reviewCount,
            'average_rating' => $reviewCount > 0
                ? round((float) $row['average_rating'], 2)
                : null,
            'rating_distribution' => [
                '1' => (int) ($row['rating_1_count'] ?? 0),
                '2' => (int) ($row['rating_2_count'] ?? 0),
                '3' => (int) ($row['rating_3_count'] ?? 0),
                '4' => (int) ($row['rating_4_count'] ?? 0),
                '5' => (int) ($row['rating_5_count'] ?? 0),
            ],
        ]);
    }

    public function findById(int $id): ?array
    {
        $row = $this
            ->query(CatalogReviewQuery::findById(), [$id])
            ->fetchOne();

        return $row ? $this->formatReview($row) : null;
    }

    private function formatReview(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'component_id' => (int) $row['component_id'],
            'user_id' => (int) $row['user_id'],
            'rating' => (int) $row['rating'],
            'title' => $row['title'],
            'comment' => $row['comment'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'user' => [
                'id' => (int) $row['user_id'],
                'name' => $row['user_name'],
            ],
        ];
    }
}