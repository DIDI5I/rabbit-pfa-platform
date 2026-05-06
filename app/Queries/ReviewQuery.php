<?php

namespace App\Queries;

class ReviewQuery
{
    public static function selectFields(): string
    {
        return "
            r.id,
            r.component_id,
            r.user_id,
            r.rating,
            r.title,
            r.comment,
            r.status,
            r.created_at,
            r.updated_at,

            c.name AS product_name,
            c.sku AS product_sku,
            c.category AS product_category,

            u.name AS user_name,
            u.email AS user_email
        ";
    }

    public static function list(string $whereSql, int $limit, int $offset): string
    {
        $fields = self::selectFields();

        return "
            SELECT {$fields}
            FROM product_reviews r
            JOIN components c ON c.id = r.component_id
            JOIN users u ON u.id = r.user_id
            {$whereSql}
            ORDER BY r.created_at DESC, r.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
    }

    public static function count(string $whereSql): string
    {
        return "
            SELECT COUNT(*) AS total
            FROM product_reviews r
            JOIN components c ON c.id = r.component_id
            JOIN users u ON u.id = r.user_id
            {$whereSql}
        ";
    }

    public static function findById(): string
    {
        $fields = self::selectFields();

        return "
            SELECT {$fields}
            FROM product_reviews r
            JOIN components c ON c.id = r.component_id
            JOIN users u ON u.id = r.user_id
            WHERE r.id = ?
            LIMIT 1
        ";
    }

    public static function updateStatus(): string
    {
        return "
            UPDATE product_reviews
            SET status = ?
            WHERE id = ?
        ";
    }

    public static function delete(): string
    {
        return "
            DELETE FROM product_reviews
            WHERE id = ?
        ";
    }

    public static function filters(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "r.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['component_id'])) {
            $where[] = "r.component_id = ?";
            $params[] = $filters['component_id'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = "r.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['rating'])) {
            $where[] = "r.rating = ?";
            $params[] = $filters['rating'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(
                r.title LIKE ?
                OR r.comment LIKE ?
                OR c.name LIKE ?
                OR c.sku LIKE ?
                OR u.name LIKE ?
                OR u.email LIKE ?
            )";

            $search = '%' . $filters['search'] . '%';

            array_push(
                $params,
                $search,
                $search,
                $search,
                $search,
                $search,
                $search
            );
        }

        return [
            'sql' => !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '',
            'params' => $params,
        ];
    }
}