<?php

namespace App\Queries;

class StockMovementQuery
{
    public static function insert(): string
    {
        return "
            INSERT INTO stock_movements (
                component_id,
                type,
                quantity,
                reason,
                reference_type,
                reference_id,
                notes,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";
    }

    public static function currentStock(): string
    {
        return "
            SELECT
                COALESCE(SUM(
                    CASE
                        WHEN type = 'in' THEN quantity
                        WHEN type = 'out' THEN -quantity
                        ELSE 0
                    END
                ), 0) AS current_stock
            FROM stock_movements
            WHERE component_id = ?
        ";
    }

    public static function findByComponent(): string
    {
        return "
            SELECT
                sm.id,
                sm.component_id,
                c.name AS component_name,
                c.sku AS component_sku,
                sm.type,
                sm.quantity,
                sm.reason,
                sm.reference_type,
                sm.reference_id,
                sm.notes,
                sm.created_by,
                u.name AS created_by_name,
                sm.created_at
            FROM stock_movements sm
            JOIN components c ON c.id = sm.component_id
            LEFT JOIN users u ON u.id = sm.created_by
            WHERE sm.component_id = ?
            ORDER BY sm.created_at DESC, sm.id DESC
        ";
    }

    public static function stockSummary(): string
    {
        return "
            SELECT
                c.id,
                c.name,
                c.sku,
                c.category,
                c.stock_qty AS legacy_stock_qty,
                c.low_stock_threshold,

                COALESCE(SUM(
                    CASE
                        WHEN sm.type = 'in' THEN sm.quantity
                        WHEN sm.type = 'out' THEN -sm.quantity
                        ELSE 0
                    END
                ), 0) AS current_stock

            FROM components c
            LEFT JOIN stock_movements sm ON sm.component_id = c.id
            WHERE c.id = ?
            GROUP BY
                c.id,
                c.name,
                c.sku,
                c.category,
                c.stock_qty,
                c.low_stock_threshold
            LIMIT 1
        ";
    }

    public static function existsByReferenceAndReason(): string
    {
        return "
            SELECT id
            FROM stock_movements
            WHERE reference_type = ?
            AND reference_id = ?
            AND reason = ?
            LIMIT 1
        ";
    }
}