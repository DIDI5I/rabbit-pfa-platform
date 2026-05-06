<?php

namespace App\Queries;

class InventoryQuery
{
    public static function all(): string
    {
        return "
            SELECT
                c.id,
                c.name,
                c.sku,
                c.category,
                c.stock_qty AS legacy_stock_qty,
                c.low_stock_threshold,

                COALESCE(stock.current_stock, 0) AS current_stock,

                s.name AS preferred_supplier,
                ps.unit_cost AS preferred_unit_cost_mad,
                ps.lead_time_days

            FROM components c

            LEFT JOIN (
                SELECT
                    component_id,
                    SUM(
                        CASE
                            WHEN type = 'in' THEN quantity
                            WHEN type = 'out' THEN -quantity
                            ELSE 0
                        END
                    ) AS current_stock
                FROM stock_movements
                GROUP BY component_id
            ) stock
                ON stock.component_id = c.id

            LEFT JOIN part_sources ps
                ON ps.id = (
                    SELECT ps2.id
                    FROM part_sources ps2
                    WHERE ps2.component_id = c.id
                    ORDER BY ps2.is_preferred DESC, ps2.id DESC
                    LIMIT 1
                )

            LEFT JOIN suppliers s
                ON s.id = ps.supplier_id

            WHERE c.is_active = 1

            ORDER BY c.name ASC
        ";
    }

    public static function alerts(): string
    {
        return "
            SELECT
                c.id,
                c.name,
                c.sku,
                c.category,
                c.stock_qty AS legacy_stock_qty,
                c.low_stock_threshold,

                COALESCE(stock.current_stock, 0) AS current_stock,

                s.name AS preferred_supplier,
                ps.unit_cost AS preferred_unit_cost_mad,
                ps.lead_time_days

            FROM components c

            LEFT JOIN (
                SELECT
                    component_id,
                    SUM(
                        CASE
                            WHEN type = 'in' THEN quantity
                            WHEN type = 'out' THEN -quantity
                            ELSE 0
                        END
                    ) AS current_stock
                FROM stock_movements
                GROUP BY component_id
            ) stock
                ON stock.component_id = c.id

            LEFT JOIN part_sources ps
                ON ps.id = (
                    SELECT ps2.id
                    FROM part_sources ps2
                    WHERE ps2.component_id = c.id
                    ORDER BY ps2.is_preferred DESC, ps2.id DESC
                    LIMIT 1
                )

            LEFT JOIN suppliers s
                ON s.id = ps.supplier_id

            WHERE c.is_active = 1
              AND COALESCE(stock.current_stock, 0) <= c.low_stock_threshold

            ORDER BY current_stock ASC, c.name ASC
        ";
    }
}