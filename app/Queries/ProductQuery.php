<?php

namespace App\Queries;

use App\Forms\ProductRequest;
use App\Queries\ProductFilterQuery;

class ProductQuery
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
            c.stock_qty,
            c.low_stock_threshold,
            c.ved_class,
            c.is_active,
            c.created_at,

            CASE
                WHEN c.stock_qty <= 0 THEN 'out'
                WHEN c.stock_qty <= c.low_stock_threshold THEN 'low'
                ELSE 'ok'
            END AS stock_status,

            EXISTS (
                SELECT 1
                FROM dependencies d
                WHERE d.parent_id = c.id
            ) AS has_children,

            CASE
                WHEN EXISTS (
                    SELECT 1 FROM dependencies d
                    WHERE d.parent_id = c.id
                )
                AND EXISTS (
                    SELECT 1 FROM dependencies d2
                    WHERE d2.child_id = c.id
                )
                    THEN 'subassembly'

                WHEN EXISTS (
                    SELECT 1 FROM dependencies d
                    WHERE d.parent_id = c.id
                )
                    THEN 'top_level_assembly'

                ELSE 'leaf'
            END AS node_role,
            (
                SELECT JSON_OBJECT(
                    'supplier_id', s.id,
                    'supplier_name', s.name,
                    'unit_cost', ps.unit_cost
                )
                FROM part_sources ps
                JOIN suppliers s ON ps.supplier_id = s.id
                WHERE ps.component_id = c.id
                AND ps.is_preferred = TRUE
                LIMIT 1
            ) AS matched_source
        ";
    }

   public static function where(ProductRequest $request): array
    {
        return ProductFilterQuery::from($request);
    }
    public static function findById(): string
    {
        $fields = self::selectFields();

        return "
            SELECT $fields
            FROM components c
            WHERE c.id = ?
            LIMIT 1
        ";
    }
    public static function insert(): string
    {
        return "
            INSERT INTO components (
                name,
                sku,
                description,
                category,
                unit_of_measure,
                stock_qty,
                low_stock_threshold,
                ved_class
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
            UPDATE components
            SET {$set}
            WHERE id = ?
        ";
    }
    public static function softDelete(): string
    {
        return "
            UPDATE components
            SET is_active = 0
            WHERE id = ?
        ";
    }
}