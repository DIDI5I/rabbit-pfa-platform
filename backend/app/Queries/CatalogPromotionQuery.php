<?php

namespace App\Queries;

class CatalogPromotionQuery
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

            CASE
                WHEN p.is_active = 0 THEN 'DISABLED'
                WHEN p.starts_at > NOW() THEN 'UPCOMING'
                WHEN p.ends_at < NOW() THEN 'EXPIRED'
                ELSE 'ACTIVE'
            END AS status
        ";
    }

    public static function activeList(int $limit, int $offset): string
    {
        $fields = self::selectFields();

        return "
            SELECT {$fields}
            FROM promotions p
            WHERE p.is_active = 1
            AND p.starts_at <= NOW()
            AND p.ends_at >= NOW()
            ORDER BY p.ends_at ASC, p.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
    }

    public static function activeCount(): string
    {
        return "
            SELECT COUNT(*) AS total
            FROM promotions p
            WHERE p.is_active = 1
            AND p.starts_at <= NOW()
            AND p.ends_at >= NOW()
        ";
    }

    public static function productActivePromotions(): string
    {
        $fields = self::selectFields();

        return "
            SELECT {$fields}
            FROM promotion_products pp
            JOIN promotions p ON p.id = pp.promotion_id
            WHERE pp.component_id = ?
            AND p.is_active = 1
            AND p.starts_at <= NOW()
            AND p.ends_at >= NOW()
            ORDER BY p.ends_at ASC, p.id DESC
        ";
    }

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
}