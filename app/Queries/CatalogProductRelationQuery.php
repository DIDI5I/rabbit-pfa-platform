<?php

namespace App\Queries;

class CatalogProductRelationQuery
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

    public static function list(): string
    {
        return "
            SELECT
                d.id AS relation_id,
                d.parent_id,
                d.child_id,
                d.relation_type,
                d.notes,

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

            FROM dependencies d
            JOIN components c
                ON c.id = d.child_id

            WHERE d.parent_id = ?
            AND c.is_active = 1
            AND d.relation_type IN (
                'replacement_part',
                'compatible_part',
                'compatible_alternative',
                'spare_part',
                'accessory',
                'related_product'
            )

            ORDER BY d.relation_type ASC, c.name ASC
        ";
    }
}