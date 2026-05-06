<?php

namespace App\Queries;

class OrderQuery
{
    public static function insertOrder(): string
    {
        return "
            INSERT INTO orders (
                client_id,
                status,
                total_amount,
                shipping_address,
                notes
            ) VALUES (?, 'pending', ?, ?, ?)
        ";
    }

    public static function insertOrderItem(): string
    {
        return "
            INSERT INTO order_items (
                order_id,
                component_id,
                quantity,
                unit_price,
                supplier_id
            ) VALUES (?, ?, ?, ?, ?)
        ";
    }

    public static function findAll(): string
    {
        return "
            SELECT
                o.id,
                o.client_id,
                u.name AS client_name,
                u.email AS client_email,
                o.status,
                o.stripe_payment_id,
                o.total_amount,
                o.shipping_address,
                o.notes,
                o.created_at,
                o.updated_at
            FROM orders o
            JOIN users u ON u.id = o.client_id
            ORDER BY o.created_at DESC, o.id DESC
        ";
    }

    public static function findById(): string
    {
        return "
            SELECT
                o.id,
                o.client_id,
                u.name AS client_name,
                u.email AS client_email,
                o.status,
                o.stripe_payment_id,
                o.total_amount,
                o.shipping_address,
                o.notes,
                o.created_at,
                o.updated_at
            FROM orders o
            JOIN users u ON u.id = o.client_id
            WHERE o.id = ?
            LIMIT 1
        ";
    }

    public static function findItemsByOrderId(): string
    {
        return "
            SELECT
                oi.id,
                oi.order_id,
                oi.component_id,
                c.name AS component_name,
                c.sku AS component_sku,
                c.category AS component_category,
                oi.quantity,
                oi.unit_price,
                ROUND(oi.quantity * oi.unit_price, 2) AS line_total,
                oi.supplier_id,
                s.name AS supplier_name,
                oi.created_at
            FROM order_items oi
            JOIN components c ON c.id = oi.component_id
            LEFT JOIN suppliers s ON s.id = oi.supplier_id
            WHERE oi.order_id = ?
            ORDER BY oi.id ASC
        ";
    }

    public static function updateStatus(): string
    {
        return "
            UPDATE orders
            SET status = ?
            WHERE id = ?
        ";
    }

    public static function findExistingStockMovementForOrder(): string
    {
        return "
            SELECT id
            FROM stock_movements
            WHERE reference_type = 'order'
            AND reference_id = ?
            AND reason = 'SALE'
            LIMIT 1
        ";
    }

    public static function findProductPrice(): string
    {
        return "
            SELECT
                ps.unit_cost
            FROM part_sources ps
            WHERE ps.component_id = ?
            AND ps.is_preferred = 1
            LIMIT 1
        ";
    }

    public static function findExistingRestoreMovementForOrder(): string
    {
        return "
            SELECT id
            FROM stock_movements
            WHERE reference_type = 'order'
            AND reference_id = ?
            AND reason = 'CANCELLED_ORDER_RESTORE'
            LIMIT 1
        ";
    }
}