<?php

namespace App\Queries;

class CatalogReviewQuery
{
    public static function productExists(): string
    {
        return "
            SELECT id
            FROM components
            WHERE id = ?
            AND is_active = 1
            LIMIT 1
        ";
    }

    public static function insert(): string
    {
        return "
            INSERT INTO product_reviews (
                component_id,
                user_id,
                rating,
                title,
                comment,
                status
            ) VALUES (?, ?, ?, ?, ?, 'pending')
        ";
    }

    public static function approvedList(int $limit, int $offset): string
    {
        return "
            SELECT
                r.id,
                r.component_id,
                r.user_id,
                r.rating,
                r.title,
                r.comment,
                r.status,
                r.created_at,
                r.updated_at,

                u.name AS user_name

            FROM product_reviews r
            JOIN users u ON u.id = r.user_id

            WHERE r.component_id = ?
            AND r.status = 'approved'

            ORDER BY r.created_at DESC, r.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
    }

    public static function approvedCount(): string
    {
        return "
            SELECT COUNT(*) AS total
            FROM product_reviews r
            WHERE r.component_id = ?
            AND r.status = 'approved'
        ";
    }

    public static function ratingSummary(): string
    {
        return "
            SELECT
                COUNT(*) AS review_count,
                AVG(rating) AS average_rating,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) AS rating_1_count,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) AS rating_2_count,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) AS rating_3_count,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) AS rating_4_count,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) AS rating_5_count
            FROM product_reviews
            WHERE component_id = ?
            AND status = 'approved'
        ";
    }

    public static function findById(): string
    {
        return "
            SELECT
                r.id,
                r.component_id,
                r.user_id,
                r.rating,
                r.title,
                r.comment,
                r.status,
                r.created_at,
                r.updated_at,

                u.name AS user_name

            FROM product_reviews r
            JOIN users u ON u.id = r.user_id

            WHERE r.id = ?
            LIMIT 1
        ";
    }
}