<?php

namespace App\Queries;

class RfqQuery
{
    public static function findById(): string
    {
        return "
            SELECT *
            FROM rfq_requests
            WHERE id = :id
            LIMIT 1
        ";
    }

    public static function accept(): string
    {
        return "
            UPDATE rfq_requests
            SET status = 'accepted',
                decision_note = :decision_note,
                decided_by = :decided_by,
                decided_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ";
    }

    public static function reject(): string
    {
        return "
            UPDATE rfq_requests
            SET status = 'rejected',
                decision_note = :decision_note,
                decided_by = :decided_by,
                decided_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ";
    }

    public static function expire(): string
    {
        return "
            UPDATE rfq_requests
            SET status = 'expired',
                decision_note = :decision_note,
                decided_by = :decided_by,
                decided_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ";
    }

    public static function open(): string
    {
        return "
            UPDATE rfq_requests
            SET status = 'open',
                updated_at = NOW()
            WHERE id = :id
        ";
    }

    public static function findActiveForProduct(): string
    {
        return "
            SELECT *
            FROM rfq_requests
            WHERE component_id = :component_id
            AND status IN ('draft', 'open', 'quoted')
            LIMIT 1
        ";
    }

    public static function createAutoDraft(): string
    {
        return "
            INSERT INTO rfq_requests (
                client_id,
                component_id,
                supplier_id,
                quantity_requested,
                status,
                client_message,
                auto_triggered,
                created_at,
                updated_at
            )
            VALUES (
                :client_id,
                :component_id,
                :supplier_id,
                :quantity_requested,
                'draft',
                :client_message,
                1,
                NOW(),
                NOW()
            )
        ";
    }

    public static function findPreferredSupplier(): string
    {
        return "
            SELECT ps.supplier_id
            FROM part_sources ps
            WHERE ps.component_id = :component_id
            AND ps.is_preferred = 1
            LIMIT 1
        ";
    }

    public static function create(): string
    {
        return "
            INSERT INTO rfq_requests (
                client_id,
                component_id,
                supplier_id,
                quantity_requested,
                status,
                quoted_price,
                auto_triggered,
                decision_note,
                client_message
            ) VALUES (
                :client_id,
                :component_id,
                :supplier_id,
                :quantity_requested,
                :status,
                :quoted_price,
                :auto_triggered,
                :decision_note,
                :client_message
            )
        ";
    }

    public static function listForOwner(): string
    {
        return "
            SELECT 
                r.id,
                r.component_id,
                c.name AS component_name,
                r.supplier_id,
                r.quantity_requested,
                r.status,
                r.quoted_price,
                r.auto_triggered,
                r.created_at
            FROM rfq_requests r
            JOIN components c ON c.id = r.component_id
            ORDER BY r.created_at DESC
        ";
    }

    public static function listForSupplier(): string
    {
        return "
            SELECT 
                r.id,
                r.component_id,
                c.name AS component_name,
                r.quantity_requested,
                r.status,
                r.quoted_price,
                r.created_at
            FROM rfq_requests r
            JOIN components c ON c.id = r.component_id
            WHERE r.supplier_id = :supplier_id
            ORDER BY r.created_at DESC
        ";
    }

    public static function findDetailById(): string
    {
        return "
            SELECT 
                r.id,
                r.component_id,
                c.name AS component_name,
                r.supplier_id,
                r.quantity_requested,
                r.status,
                r.quoted_price,
                r.auto_triggered,
                r.decision_note,
                r.created_at
            FROM rfq_requests r
            LEFT JOIN components c ON c.id = r.component_id
            WHERE r.id = :id
            LIMIT 1
        ";
    }

    public static function updateQuote(): string
    {
        return "
            UPDATE rfq_requests
            SET 
                quoted_price = :quoted_price,
                status = 'quoted'
            WHERE id = :id
        ";
    }
}