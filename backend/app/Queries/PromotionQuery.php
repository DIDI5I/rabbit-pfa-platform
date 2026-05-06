<?php

namespace App\Queries;

class PromotionQuery
{
    public static function selectFields(): string
    {
        return "
            p.id,
            p.title,
            p.description,
            p.discount_type,
            p.discount_value,
            p.starts_at,
            p.ends_at,
            p.is_active,
            p.created_by,
            p.created_at,
            p.updated_at,

            CASE
                WHEN p.is_active = 0 THEN 'DISABLED'
                WHEN p.starts_at > NOW() THEN 'UPCOMING'
                WHEN p.ends_at < NOW() THEN 'EXPIRED'
                ELSE 'ACTIVE'
            END AS status,

            (
                SELECT COUNT(*)
                FROM promotion_products pp
                WHERE pp.promotion_id = p.id
            ) AS product_count
        ";
    }

    public static function list(string $whereSql, int $limit, int $offset): string
    {
        $fields = self::selectFields();

        return "
            SELECT {$fields}
            FROM promotions p
            {$whereSql}
            ORDER BY p.created_at DESC, p.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
    }

    public static function count(string $whereSql): string
    {
        return "
            SELECT COUNT(*) AS total
            FROM promotions p
            {$whereSql}
        ";
    }

    public static function findById(): string
    {
        $fields = self::selectFields();

        return "
            SELECT {$fields}
            FROM promotions p
            WHERE p.id = ?
            LIMIT 1
        ";
    }

    public static function insert(): string
    {
        return "
            INSERT INTO promotions (
                title,
                description,
                discount_type,
                discount_value,
                starts_at,
                ends_at,
                is_active,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";
    }

    public static function update(array $fields): string
    {
        $set = implode(', ', array_map(
            fn ($field) => "{$field} = ?",
            $fields
        ));

        return "
            UPDATE promotions
            SET {$set}
            WHERE id = ?
        ";
    }

    public static function delete(): string
    {
        return "
            DELETE FROM promotions
            WHERE id = ?
        ";
    }

    public static function filters(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "
                CASE
                    WHEN p.is_active = 0 THEN 'DISABLED'
                    WHEN p.starts_at > NOW() THEN 'UPCOMING'
                    WHEN p.ends_at < NOW() THEN 'EXPIRED'
                    ELSE 'ACTIVE'
                END = ?
            ";
            $params[] = $filters['status'];
        }

        if (!empty($filters['active_only'])) {
            $where[] = "p.is_active = 1";
        }

        if (!empty($filters['search'])) {
            $where[] = "(p.title LIKE ? OR p.description LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        return [
            'sql' => !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '',
            'params' => $params,
        ];
    }
}