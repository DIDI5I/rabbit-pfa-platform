<?php

namespace App\Queries;

class NotificationQuery
{
    public static function insert(): string
    {
        return "
            INSERT INTO notifications (
                user_id,
                role,
                type,
                title,
                message,
                reference_type,
                reference_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
    }

    public static function listForUser(): string
    {
        return "
            SELECT
                id,
                user_id,
                role,
                type,
                title,
                message,
                reference_type,
                reference_id,
                is_read,
                created_at,
                read_at
            FROM notifications
            WHERE
                (user_id = ? OR role = ?)
        ";
    }

    public static function countForUser(): string
    {
        return "
            SELECT COUNT(*) AS total
            FROM notifications
            WHERE
                (user_id = ? OR role = ?)
        ";
    }

    public static function unreadCountForUser(): string
    {
        return "
            SELECT COUNT(*) AS unread_count
            FROM notifications
            WHERE
                (user_id = ? OR role = ?)
                AND is_read = 0
        ";
    }

    public static function markAsRead(): string
    {
        return "
            UPDATE notifications
            SET
                is_read = 1,
                read_at = NOW()
            WHERE id = ?
            AND (user_id = ? OR role = ?)
        ";
    }

    public static function markAllAsRead(): string
    {
        return "
            UPDATE notifications
            SET
                is_read = 1,
                read_at = NOW()
            WHERE
                (user_id = ? OR role = ?)
                AND is_read = 0
        ";
    }
}