<?php

namespace App\Queries;

class PurchaseLotQuery
{
    public static function insert(): string
    {
        return "
            INSERT INTO purchase_lots (
                component_id,
                supplier_id,
                quantity_received,
                supplier_unit_price,
                supplier_total_price,
                transport_cost,
                customs_cost,
                handling_cost,
                packaging_cost,
                order_preparation_cost,
                other_cost,
                total_purchase_cost,
                unit_purchase_cost,
                status,
                purchase_date,
                reference_type,
                reference_id,
                notes,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
    }

    public static function lastInsertId(): string
    {
        return "SELECT LAST_INSERT_ID() AS id";
    }

    public static function findAll(): string
    {
        return "
            SELECT
                pl.id,
                pl.component_id,
                c.name AS component_name,
                c.sku AS component_sku,
                pl.supplier_id,
                s.name AS supplier_name,
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
                pl.status,
                pl.purchase_date,
                pl.reference_type,
                pl.reference_id,
                pl.notes,
                pl.created_by,
                u.name AS created_by_name,
                pl.created_at
            FROM purchase_lots pl
            JOIN components c ON c.id = pl.component_id
            JOIN suppliers s ON s.id = pl.supplier_id
            LEFT JOIN users u ON u.id = pl.created_by
            ORDER BY pl.purchase_date DESC, pl.id DESC
        ";
    }

    public static function findByComponent(): string
    {
        return "
            SELECT
                pl.id,
                pl.component_id,
                c.name AS component_name,
                c.sku AS component_sku,
                pl.supplier_id,
                s.name AS supplier_name,
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
                pl.status,
                pl.purchase_date,
                pl.reference_type,
                pl.reference_id,
                pl.notes,
                pl.created_by,
                u.name AS created_by_name,
                pl.created_at
            FROM purchase_lots pl
            JOIN components c ON c.id = pl.component_id
            JOIN suppliers s ON s.id = pl.supplier_id
            LEFT JOIN users u ON u.id = pl.created_by
            WHERE pl.component_id = ?
            ORDER BY pl.purchase_date DESC, pl.id DESC
        ";
    }

    public static function findById(): string
    {
        return "
            SELECT
                pl.id,
                pl.component_id,
                c.name AS component_name,
                c.sku AS component_sku,
                pl.supplier_id,
                s.name AS supplier_name,
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
                pl.status,
                pl.purchase_date,
                pl.reference_type,
                pl.reference_id,
                pl.notes,
                pl.created_by,
                u.name AS created_by_name,
                pl.created_at
            FROM purchase_lots pl
            JOIN components c ON c.id = pl.component_id
            JOIN suppliers s ON s.id = pl.supplier_id
            LEFT JOIN users u ON u.id = pl.created_by
            WHERE pl.id = ?
            LIMIT 1
        ";
    }

    public static function existsByReference(): string
    {
        return "
            SELECT id
            FROM purchase_lots
            WHERE reference_type = ?
            AND reference_id = ?
            LIMIT 1
        ";
    }

    public static function updateCostsAndStatus(): string
    {
        return "
            UPDATE purchase_lots
            SET
                transport_cost = ?,
                customs_cost = ?,
                handling_cost = ?,
                packaging_cost = ?,
                order_preparation_cost = ?,
                other_cost = ?,
                total_purchase_cost = ?,
                unit_purchase_cost = ?,
                status = ?
            WHERE id = ?
        ";
    }

    public static function existsStockMovementForPurchaseLot(): string
    {
        return "
            SELECT id
            FROM stock_movements
            WHERE reference_type = 'purchase_lot'
            AND reference_id = ?
            AND reason = 'PURCHASE_RECEIVED'
            LIMIT 1
        ";
    }
}