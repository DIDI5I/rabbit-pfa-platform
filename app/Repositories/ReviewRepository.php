<?php

namespace App\Repositories;

use App\Queries\ReviewQuery;
use App\Support\ApiResponse;

class ReviewRepository extends Repository
{
    public function list(array $filters, int $page = 1, int $limit = 20): array
    {
        $page = max(1, $page);
        $limit = max(1, min($limit, 100));
        $offset = ($page - 1) * $limit;

        $where = ReviewQuery::filters($filters);

        $totalRow = $this
            ->query(ReviewQuery::count($where['sql']), $where['params'])
            ->fetchOne();

        $total = (int) ($totalRow['total'] ?? 0);

        $rows = $this
            ->query(
                ReviewQuery::list($where['sql'], $limit, $offset),
                $where['params']
            )
            ->fetchMany();

        return ApiResponse::success('Reviews fetched successfully', [
            'items' => array_map(fn ($row) => $this->formatReview($row), $rows),
            'filters' => $filters,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $total > 0 ? (int) ceil($total / $limit) : 0,
            ],
            'summary' => [
                'by_status' => $this->countByStatus($filters),
            ],
        ]);
    }

    public function findById(int $id): ?array
    {
        $row = $this
            ->query(ReviewQuery::findById(), [$id])
            ->fetchOne();

        return $row ? $this->formatReview($row) : null;
    }

    public function existsById(int $id): bool
    {
        return $this
            ->query("SELECT id FROM product_reviews WHERE id = ? LIMIT 1", [$id])
            ->exists();
    }

    public function updateStatus(int $id, string $status): ?array
    {
        $this->query(ReviewQuery::updateStatus(), [$status, $id]);

        return $this->findById($id);
    }

    public function delete(int $id): void
    {
        $this->query(ReviewQuery::delete(), [$id]);
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

            'product' => [
                'id' => (int) $row['component_id'],
                'name' => $row['product_name'],
                'sku' => $row['product_sku'],
                'category' => $row['product_category'],
            ],

            'user' => [
                'id' => (int) $row['user_id'],
                'name' => $row['user_name'],
                'email' => $row['user_email'],
            ],
        ];
    }

    private function countByStatus(array $filters): array
    {
        /*
         * Keep this simple for V1: status counts across all reviews,
         * not affected by current list filters.
         */
        $rows = $this
            ->query("
                SELECT status, COUNT(*) AS total
                FROM product_reviews
                GROUP BY status
            ")
            ->fetchMany();

        $counts = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];

        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }
}