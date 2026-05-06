<?php

namespace App\Queries;

class DashboardStockQuery
{
    public static function generalProductCounts(): string
    {
        return "
            SELECT
                COUNT(*) AS total_products,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_products
            FROM components
        ";
    }

    public static function inventoryStatusCounts(): string
    {
        return "
            SELECT
                SUM(CASE WHEN current_stock <= 0 THEN 1 ELSE 0 END) AS out_of_stock_count,
                SUM(CASE WHEN current_stock > 0 AND current_stock <= low_stock_threshold THEN 1 ELSE 0 END) AS low_stock_count,
                SUM(CASE WHEN current_stock > low_stock_threshold THEN 1 ELSE 0 END) AS stock_ok_count
            FROM (
                SELECT
                    c.id,
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
                WHERE c.is_active = 1
                GROUP BY c.id, c.low_stock_threshold
            ) stock_summary
        ";
    }

    public static function stockMovementCounts(): string
    {
        return "
            SELECT
                COUNT(*) AS total_stock_movements,
                SUM(CASE WHEN type = 'in' THEN 1 ELSE 0 END) AS stock_in_movements,
                SUM(CASE WHEN type = 'out' THEN 1 ELSE 0 END) AS stock_out_movements,
                SUM(CASE
                    WHEN created_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
                    THEN 1 ELSE 0
                END) AS stock_movements_this_month,
                SUM(CASE
                    WHEN type = 'in'
                    AND created_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
                    THEN 1 ELSE 0
                END) AS stock_in_movements_this_month,
                SUM(CASE
                    WHEN type = 'out'
                    AND created_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
                    THEN 1 ELSE 0
                END) AS stock_out_movements_this_month
            FROM stock_movements
        ";
    }

    public static function purchaseLotStatusCounts(): string
    {
        return "
            SELECT
                COUNT(*) AS total_purchase_lots,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_purchase_lots,
                SUM(CASE WHEN status = 'finalized' THEN 1 ELSE 0 END) AS finalized_purchase_lots,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_purchase_lots
            FROM purchase_lots
        ";
    }

    public static function totalInventoryValue(): string
    {
        return "
            SELECT
                COALESCE(SUM(stock_summary.current_stock * COALESCE(latest_cost.unit_purchase_cost, ps.unit_cost, 0)), 0) AS total_inventory_value
            FROM (
                SELECT
                    c.id AS component_id,
                    COALESCE(SUM(
                        CASE
                            WHEN sm.type = 'in' THEN sm.quantity
                            WHEN sm.type = 'out' THEN -sm.quantity
                            ELSE 0
                        END
                    ), 0) AS current_stock
                FROM components c
                LEFT JOIN stock_movements sm ON sm.component_id = c.id
                WHERE c.is_active = 1
                GROUP BY c.id
            ) stock_summary

            LEFT JOIN (
                SELECT
                    pl.component_id,
                    pl.unit_purchase_cost
                FROM purchase_lots pl
                INNER JOIN (
                    SELECT
                        component_id,
                        MAX(id) AS latest_id
                    FROM purchase_lots
                    WHERE status = 'finalized'
                    GROUP BY component_id
                ) latest ON latest.latest_id = pl.id
            ) latest_cost ON latest_cost.component_id = stock_summary.component_id

            LEFT JOIN part_sources ps
                ON ps.component_id = stock_summary.component_id
                AND ps.is_preferred = 1
        ";
    }

    public static function productsPerformance(): string
    {
        return "
            SELECT
                c.id,
                c.name,
                c.sku,
                c.category,
                c.low_stock_threshold,

                COALESCE(SUM(
                    CASE
                        WHEN sm.type = 'in' THEN sm.quantity
                        WHEN sm.type = 'out' THEN -sm.quantity
                        ELSE 0
                    END
                ), 0) AS current_stock,

                COALESCE(SUM(
                    CASE
                        WHEN sm.type = 'in' THEN sm.quantity
                        ELSE 0
                    END
                ), 0) AS stock_in_quantity,

                COALESCE(SUM(
                    CASE
                        WHEN sm.type = 'out' THEN sm.quantity
                        ELSE 0
                    END
                ), 0) AS stock_out_quantity,

                COUNT(sm.id) AS movement_count,
                MAX(sm.created_at) AS last_movement_at,

                COALESCE(latest_cost.unit_purchase_cost, ps.unit_cost, 0) AS latest_unit_purchase_cost

            FROM components c

            LEFT JOIN stock_movements sm
                ON sm.component_id = c.id

            LEFT JOIN (
                SELECT
                    pl.component_id,
                    pl.unit_purchase_cost
                FROM purchase_lots pl
                INNER JOIN (
                    SELECT
                        component_id,
                        MAX(id) AS latest_id
                    FROM purchase_lots
                    WHERE status = 'finalized'
                    GROUP BY component_id
                ) latest ON latest.latest_id = pl.id
            ) latest_cost ON latest_cost.component_id = c.id

            LEFT JOIN part_sources ps
                ON ps.component_id = c.id
                AND ps.is_preferred = 1

            WHERE c.is_active = 1

            GROUP BY
                c.id,
                c.name,
                c.sku,
                c.category,
                c.low_stock_threshold,
                latest_cost.unit_purchase_cost,
                ps.unit_cost

            ORDER BY c.name ASC
        ";
    }

    public static function productPerformanceById(): string
    {
        return "
            SELECT
                c.id,
                c.name,
                c.sku,
                c.category,
                c.low_stock_threshold,

                COALESCE(SUM(
                    CASE
                        WHEN sm.type = 'in' THEN sm.quantity
                        WHEN sm.type = 'out' THEN -sm.quantity
                        ELSE 0
                    END
                ), 0) AS current_stock,

                COALESCE(SUM(
                    CASE
                        WHEN sm.type = 'in' THEN sm.quantity
                        ELSE 0
                    END
                ), 0) AS stock_in_quantity,

                COALESCE(SUM(
                    CASE
                        WHEN sm.type = 'out' THEN sm.quantity
                        ELSE 0
                    END
                ), 0) AS stock_out_quantity,

                COUNT(sm.id) AS movement_count,
                MAX(sm.created_at) AS last_movement_at,

                COALESCE(latest_cost.unit_purchase_cost, ps.unit_cost, 0) AS latest_unit_purchase_cost

            FROM components c

            LEFT JOIN stock_movements sm
                ON sm.component_id = c.id

            LEFT JOIN (
                SELECT
                    pl.component_id,
                    pl.unit_purchase_cost
                FROM purchase_lots pl
                INNER JOIN (
                    SELECT
                        component_id,
                        MAX(id) AS latest_id
                    FROM purchase_lots
                    WHERE status = 'finalized'
                    GROUP BY component_id
                ) latest ON latest.latest_id = pl.id
            ) latest_cost ON latest_cost.component_id = c.id

            LEFT JOIN part_sources ps
                ON ps.component_id = c.id
                AND ps.is_preferred = 1

            WHERE c.id = ?
            AND c.is_active = 1

            GROUP BY
                c.id,
                c.name,
                c.sku,
                c.category,
                c.low_stock_threshold,
                latest_cost.unit_purchase_cost,
                ps.unit_cost

            LIMIT 1
        ";
    }

    public static function productMovementTimeline(): string
    {
        return "
            SELECT
                DATE(sm.created_at) AS movement_date,

                COALESCE(SUM(
                    CASE
                        WHEN sm.type = 'in' THEN sm.quantity
                        ELSE 0
                    END
                ), 0) AS stock_in,

                COALESCE(SUM(
                    CASE
                        WHEN sm.type = 'out' THEN sm.quantity
                        ELSE 0
                    END
                ), 0) AS stock_out,

                COALESCE(SUM(
                    CASE
                        WHEN sm.type = 'in' THEN sm.quantity
                        WHEN sm.type = 'out' THEN -sm.quantity
                        ELSE 0
                    END
                ), 0) AS net_change

            FROM stock_movements sm
            WHERE sm.component_id = ?
            GROUP BY DATE(sm.created_at)
            ORDER BY movement_date ASC
        ";
    }

    public static function productCostHistory(): string
    {
        return "
            SELECT
                pl.id,
                pl.purchase_date,
                pl.status,
                pl.quantity_received,
                pl.supplier_unit_price,
                pl.supplier_total_price,
                pl.transport_cost,
                pl.customs_cost,
                pl.handling_cost,
                pl.packaging_cost,
                pl.order_preparation_cost,
                pl.other_cost,
                pl.total_purchase_cost,
                pl.unit_purchase_cost,
                pl.reference_type,
                pl.reference_id

            FROM purchase_lots pl
            WHERE pl.component_id = ?
            ORDER BY pl.purchase_date ASC, pl.id ASC
        ";
    }

    public static function abcBaseProducts(): string
    {
        return "
            SELECT
                c.id,
                c.name,
                c.sku,
                c.category,
                c.low_stock_threshold,

                COALESCE(SUM(
                    CASE
                        WHEN sm.type = 'in' THEN sm.quantity
                        WHEN sm.type = 'out' THEN -sm.quantity
                        ELSE 0
                    END
                ), 0) AS current_stock,

                COALESCE(SUM(
                    CASE
                        WHEN sm.type = 'in' THEN sm.quantity
                        ELSE 0
                    END
                ), 0) AS stock_in_quantity,

                COALESCE(SUM(
                    CASE
                        WHEN sm.type = 'out' THEN sm.quantity
                        ELSE 0
                    END
                ), 0) AS stock_out_quantity,

                COUNT(sm.id) AS movement_count,
                MAX(sm.created_at) AS last_movement_at,

                COALESCE(latest_cost.unit_purchase_cost, ps.unit_cost, 0) AS latest_unit_purchase_cost

            FROM components c

            LEFT JOIN stock_movements sm
                ON sm.component_id = c.id

            LEFT JOIN (
                SELECT
                    pl.component_id,
                    pl.unit_purchase_cost
                FROM purchase_lots pl
                INNER JOIN (
                    SELECT
                        component_id,
                        MAX(id) AS latest_id
                    FROM purchase_lots
                    WHERE status = 'finalized'
                    GROUP BY component_id
                ) latest ON latest.latest_id = pl.id
            ) latest_cost ON latest_cost.component_id = c.id

            LEFT JOIN part_sources ps
                ON ps.component_id = c.id
                AND ps.is_preferred = 1

            WHERE c.is_active = 1

            GROUP BY
                c.id,
                c.name,
                c.sku,
                c.category,
                c.low_stock_threshold,
                latest_cost.unit_purchase_cost,
                ps.unit_cost
        ";
    }
}