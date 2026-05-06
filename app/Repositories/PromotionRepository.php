<?php

namespace App\Repositories;

use App\Forms\StorePromotionRequest;
use App\Forms\UpdatePromotionRequest;
use App\Queries\PromotionQuery;
use App\Support\ApiResponse;

class PromotionRepository extends Repository
{
    public function list(array $filters, int $page = 1, int $limit = 20): array
    {
        $page = max(1, $page);
        $limit = max(1, min($limit, 100));
        $offset = ($page - 1) * $limit;

        $where = PromotionQuery::filters($filters);

        $totalRow = $this
            ->query(PromotionQuery::count($where['sql']), $where['params'])
            ->fetchOne();

        $total = (int) ($totalRow['total'] ?? 0);

        $rows = $this
            ->query(
                PromotionQuery::list($where['sql'], $limit, $offset),
                $where['params']
            )
            ->fetchMany();

        return ApiResponse::success('Promotions fetched successfully', [
            'items' => array_map(fn ($row) => $this->formatPromotion($row), $rows),
            'filters' => $filters,
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
            ->query(PromotionQuery::findById(), [$id])
            ->fetchOne();

        return $row ? $this->formatPromotion($row) : null;
    }

    public function create(StorePromotionRequest $request): array
    {
        $this->query(PromotionQuery::insert(), [
            $request->title(),
            $request->description(),
            $request->discountType(),
            $request->discountValue(),
            $request->startsAt(),
            $request->endsAt(),
            $request->isActive() ? 1 : 0,
            $request->createdBy(),
        ]);

        $id = (int) $this->connection->lastInsertId();

        return $this->findById($id);
    }

    public function update(int $id, UpdatePromotionRequest $request): ?array
    {
        $data = $request->data();

        if (empty($data)) {
            return $this->findById($id);
        }

        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = (bool) $data['is_active'] ? 1 : 0;
        }

        $this->query(
            PromotionQuery::update(array_keys($data)),
            [...array_values($data), $id]
        );

        return $this->findById($id);
    }

    public function delete(int $id): void
    {
        $this->query(PromotionQuery::delete(), [$id]);
    }

    public function existsById(int $id): bool
    {
        return $this
            ->query("SELECT id FROM promotions WHERE id = ? LIMIT 1", [$id])
            ->exists();
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
            'is_active' => (bool) $row['is_active'],
            'status' => $row['status'],
            'product_count' => (int) $row['product_count'],
            'created_by' => isset($row['created_by']) ? (int) $row['created_by'] : null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }
}