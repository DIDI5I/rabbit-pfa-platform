<?php

namespace App\Queries;

class RecommendationQuery
{
    public static function findProduct(): string
    {
        return "
            SELECT
                c.id,
                c.name,
                c.sku,
                c.description,
                c.category,
                c.unit_of_measure,
                c.stock_qty,
                c.low_stock_threshold,
                c.is_active,

                CASE
                    WHEN c.stock_qty <= 0 THEN 'out'
                    WHEN c.stock_qty <= c.low_stock_threshold THEN 'low'
                    ELSE 'ok'
                END AS stock_status,

                (
                    SELECT JSON_OBJECT(
                        'supplier_id', s.id,
                        'supplier_name', s.name,
                        'unit_cost', ps.unit_cost
                    )
                    FROM part_sources ps
                    JOIN suppliers s ON s.id = ps.supplier_id
                    WHERE ps.component_id = c.id
                    AND ps.is_preferred = 1
                    LIMIT 1
                ) AS matched_source

            FROM components c
            WHERE c.id = ?
            AND c.is_active = 1
            LIMIT 1
        ";
    }

    public static function explicitRelations(): string
    {
        return "
            SELECT
                d.id AS dependency_id,
                d.parent_id,
                d.child_id,
                d.qty_required,
                d.relation_type,
                d.is_phantom,

                child.id,
                child.name,
                child.sku,
                child.description,
                child.category,
                child.unit_of_measure,
                child.stock_qty,
                child.low_stock_threshold,
                child.is_active,

                CASE
                    WHEN child.stock_qty <= 0 THEN 'out'
                    WHEN child.stock_qty <= child.low_stock_threshold THEN 'low'
                    ELSE 'ok'
                END AS stock_status,

                (
                    SELECT JSON_OBJECT(
                        'supplier_id', s.id,
                        'supplier_name', s.name,
                        'unit_cost', ps.unit_cost
                    )
                    FROM part_sources ps
                    JOIN suppliers s ON s.id = ps.supplier_id
                    WHERE ps.component_id = child.id
                    AND ps.is_preferred = 1
                    LIMIT 1
                ) AS matched_source

            FROM dependencies d
            JOIN components child ON child.id = d.child_id
            WHERE d.parent_id = ?
            AND child.is_active = 1
            ORDER BY d.relation_type ASC, child.name ASC
        ";
    }

    public static function sameCategoryProducts(int $limit = 6): string
    {
        $limit = max(1, min($limit, 20));

        return "
            SELECT
                c.id,
                c.name,
                c.sku,
                c.description,
                c.category,
                c.unit_of_measure,
                c.stock_qty,
                c.low_stock_threshold,
                c.is_active,

                CASE
                    WHEN c.stock_qty <= 0 THEN 'out'
                    WHEN c.stock_qty <= c.low_stock_threshold THEN 'low'
                    ELSE 'ok'
                END AS stock_status,

                (
                    SELECT JSON_OBJECT(
                        'supplier_id', s.id,
                        'supplier_name', s.name,
                        'unit_cost', ps.unit_cost
                    )
                    FROM part_sources ps
                    JOIN suppliers s ON s.id = ps.supplier_id
                    WHERE ps.component_id = c.id
                    AND ps.is_preferred = 1
                    LIMIT 1
                ) AS matched_source

            FROM components c
            WHERE c.category = ?
            AND c.id != ?
            AND c.is_active = 1
            ORDER BY c.name ASC
            LIMIT {$limit}
        ";
    }

    public static function sameSupplierProducts(int $limit = 6): string
    {
        $limit = max(1, min($limit, 20));

        return "
            SELECT DISTINCT
                c.id,
                c.name,
                c.sku,
                c.description,
                c.category,
                c.unit_of_measure,
                c.stock_qty,
                c.low_stock_threshold,
                c.is_active,

                CASE
                    WHEN c.stock_qty <= 0 THEN 'out'
                    WHEN c.stock_qty <= c.low_stock_threshold THEN 'low'
                    ELSE 'ok'
                END AS stock_status,

                JSON_OBJECT(
                    'supplier_id', s.id,
                    'supplier_name', s.name,
                    'unit_cost', ps.unit_cost
                ) AS matched_source

            FROM part_sources source_ps

            JOIN part_sources ps
                ON ps.supplier_id = source_ps.supplier_id

            JOIN suppliers s
                ON s.id = ps.supplier_id

            JOIN components c
                ON c.id = ps.component_id

            WHERE source_ps.component_id = ?
            AND c.id != ?
            AND c.is_active = 1
            AND ps.is_preferred = 1
            AND source_ps.is_preferred = 1

            ORDER BY c.name ASC
            LIMIT {$limit}
        ";
    }
}