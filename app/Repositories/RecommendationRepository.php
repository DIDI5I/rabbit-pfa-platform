<?php

namespace App\Repositories;

use App\Queries\RecommendationQuery;

class RecommendationRepository extends Repository
{
    public function findProduct(int $productId): ?array
    {
        $row = $this
            ->query(RecommendationQuery::findProduct(), [$productId])
            ->fetchOne();

        if (!$row) {
            return null;
        }

        return $this->castProductRow($row);
    }

    public function explicitRelations(int $productId): array
    {
        $rows = $this
            ->query(RecommendationQuery::explicitRelations(), [$productId])
            ->fetchMany();

        foreach ($rows as &$row) {
            $row = $this->castProductRow($row);

            $row['dependency_id'] = (int) $row['dependency_id'];
            $row['parent_id'] = (int) $row['parent_id'];
            $row['child_id'] = (int) $row['child_id'];
            $row['qty_required'] = (float) $row['qty_required'];
            $row['is_phantom'] = (bool) $row['is_phantom'];
        }

        unset($row);

        return $rows;
    }

    public function sameCategoryProducts(string $category, int $productId, int $limit = 6): array
    {
        $rows = $this
            ->query(
                RecommendationQuery::sameCategoryProducts($limit),
                [$category, $productId]
            )
            ->fetchMany();

        return $this->castProductRows($rows);
    }

    public function sameSupplierProducts(int $productId, int $limit = 6): array
    {
        $rows = $this
            ->query(
                RecommendationQuery::sameSupplierProducts($limit),
                [$productId, $productId]
            )
            ->fetchMany();

        return $this->castProductRows($rows);
    }

    private function castProductRows(array $rows): array
    {
        foreach ($rows as &$row) {
            $row = $this->castProductRow($row);
        }

        unset($row);

        return $rows;
    }

    private function castProductRow(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['stock_qty'] = (float) $row['stock_qty'];
        $row['low_stock_threshold'] = (float) $row['low_stock_threshold'];
        $row['is_active'] = (bool) $row['is_active'];

        if (isset($row['matched_source']) && is_string($row['matched_source'])) {
            $decoded = json_decode($row['matched_source'], true);
            $row['matched_source'] = is_array($decoded) ? $decoded : null;
        }

        if (isset($row['matched_source']) && is_array($row['matched_source'])) {
            $row['matched_source']['supplier_id'] = isset($row['matched_source']['supplier_id'])
                ? (int) $row['matched_source']['supplier_id']
                : null;

            $row['matched_source']['unit_cost'] = isset($row['matched_source']['unit_cost'])
                ? (float) $row['matched_source']['unit_cost']
                : null;
        }

        return $row;
    }
}