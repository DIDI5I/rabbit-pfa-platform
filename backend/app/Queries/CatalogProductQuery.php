<?php

namespace App\Queries;

class CatalogProductQuery
{
    public static function selectFields(): string
    {
        return "
            c.id,
            c.name,
            c.sku,
            c.description,
            c.category,
            c.unit_of_measure,
            c.is_active,

            CASE
                WHEN c.stock_qty <= 0 THEN 'OUT_OF_STOCK'
                WHEN c.stock_qty <= c.low_stock_threshold THEN 'LOW_AVAILABILITY'
                ELSE 'AVAILABLE'
            END AS availability_status
        ";
    }

    public static function list(string $whereSql, int $limit, int $offset): string
    {
        $fields = self::selectFields();

        return "
            SELECT
                {$fields}
            FROM components c
            {$whereSql}
            ORDER BY c.name ASC
            LIMIT {$limit} OFFSET {$offset}
        ";
    }

    public static function count(string $whereSql): string
    {
        return "
            SELECT COUNT(*) AS total
            FROM components c
            {$whereSql}
        ";
    }

    public static function findById(): string
    {
        $fields = self::selectFields();

        return "
            SELECT
                {$fields}
            FROM components c
            WHERE c.id = ?
            AND c.is_active = 1
            LIMIT 1
        ";
    }

    public static function filters(array $filters): array
    {
        $where = [
            "c.is_active = 1"
        ];

        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(
                c.name LIKE ?
                OR c.sku LIKE ?
                OR c.description LIKE ?
            )";

            $search = '%' . $filters['search'] . '%';

            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['category'])) {
            $where[] = "c.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['availability'])) {
            $availabilitySql = "
                CASE
                    WHEN c.stock_qty <= 0 THEN 'OUT_OF_STOCK'
                    WHEN c.stock_qty <= c.low_stock_threshold THEN 'LOW_AVAILABILITY'
                    ELSE 'AVAILABLE'
                END
            ";

            $where[] = "{$availabilitySql} = ?";
            $params[] = $filters['availability'];
        }

        return [
            'sql' => 'WHERE ' . implode(' AND ', $where),
            'params' => $params,
        ];
    }
}