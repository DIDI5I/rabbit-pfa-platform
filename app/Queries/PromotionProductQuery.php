<?php

namespace App\Queries;

class PromotionProductQuery
{
    public static function promotionExists(): string
    {
        return "
            SELECT id
            FROM promotions
            WHERE id = ?
            LIMIT 1
        ";
    }

    public static function componentExists(): string
    {
        return "
            SELECT id
            FROM components
            WHERE id = ?
            AND is_active = 1
            LIMIT 1
        ";
    }

    public static function list(): string
    {
        return "
            SELECT
                pp.id AS promotion_product_id,
                pp.promotion_id,
                pp.component_id,
                pp.created_at,

                c.id AS product_id,
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

            FROM promotion_products pp
            JOIN components c ON c.id = pp.component_id

            WHERE pp.promotion_id = ?
            AND c.is_active = 1

            ORDER BY pp.created_at DESC, pp.id DESC
        ";
    }

    public static function attach(): string
    {
        return "
            INSERT IGNORE INTO promotion_products (
                promotion_id,
                component_id
            ) VALUES (?, ?)
        ";
    }

    public static function detach(): string
    {
        return "
            DELETE FROM promotion_products
            WHERE promotion_id = ?
            AND component_id = ?
        ";
    }
}