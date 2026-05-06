<?php

namespace App\Queries;

class StockIntelligenceQuery
{
    public static function baseProductRows(): string
    {
        return "
            SELECT
                c.id AS product_id,
                c.name,
                c.sku,
                c.category,
                c.unit_of_measure,
                c.low_stock_threshold,
                c.ved_class,
                c.is_active,

                COALESCE(SUM(
                    CASE
                        WHEN sm.type = 'in' THEN sm.quantity
                        WHEN sm.type = 'out' THEN -sm.quantity
                        ELSE 0
                    END
                ), 0) AS current_stock,

                ps.supplier_id AS preferred_supplier_id,
                s.name AS preferred_supplier_name,
                ps.lead_time_days AS supplier_lead_time_days,
                ps.unit_cost AS latest_unit_purchase_cost

            FROM components c

            LEFT JOIN stock_movements sm
                ON sm.component_id = c.id

            LEFT JOIN (
                SELECT ps1.*
                FROM part_sources ps1
                JOIN (
                    SELECT
                        component_id,
                        MIN(id) AS selected_source_id
                    FROM part_sources
                    WHERE is_preferred = 1
                    GROUP BY component_id
                ) selected
                    ON selected.selected_source_id = ps1.id
            ) ps
                ON ps.component_id = c.id

            LEFT JOIN suppliers s
                ON s.id = ps.supplier_id

            WHERE c.is_active = 1

            GROUP BY
                c.id,
                c.name,
                c.sku,
                c.category,
                c.unit_of_measure,
                c.low_stock_threshold,
                c.ved_class,
                c.is_active,
                ps.supplier_id,
                s.name,
                ps.lead_time_days,
                ps.unit_cost

            ORDER BY c.id ASC
        ";
    }

    public static function outflowSummary(): string
    {
        return "
            SELECT
                sm.component_id AS product_id,
                COUNT(*) AS out_movement_count,
                COALESCE(SUM(sm.quantity), 0) AS stock_out_quantity,
                MAX(sm.created_at) AS last_out_at
            FROM stock_movements sm
            WHERE sm.type = 'out'
            AND sm.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY sm.component_id
        ";
    }

    public static function outflowHistory(): string
{
    return "
        SELECT
            sm.component_id AS product_id,
            sm.quantity,
            sm.created_at
        FROM stock_movements sm
        WHERE sm.type = 'out'
        AND sm.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY sm.component_id ASC, sm.created_at ASC
    ";
}
}