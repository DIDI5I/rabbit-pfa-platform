<?php

namespace App\Queries;

class DependencyQuery
{
    public static function findChildren(): string
    {
        return "
            SELECT
                d.id,
                d.parent_id,
                d.child_id,
                d.relation_type,
                c.name AS child_name,
                c.sku AS child_sku,
                c.category AS child_category,
                d.qty_required,
                d.uom,
                d.is_phantom,
                d.notes
            FROM dependencies d
            JOIN components c ON c.id = d.child_id
            WHERE d.parent_id = ?
            ORDER BY d.relation_type ASC, c.name ASC
        ";
    }

    public static function insert(): string
    {
        return "
            INSERT INTO dependencies (
                parent_id,
                child_id,
                relation_type,
                qty_required,
                uom,
                is_phantom,
                notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
    }

    public static function delete(): string
    {
        return "
            DELETE FROM dependencies
            WHERE parent_id = ?
            AND id = ?
        ";
    }

    public static function update(array $fields): string
    {
        $set = implode(', ', array_map(
            fn ($f) => "{$f} = ?",
            $fields
        ));

        return "
            UPDATE dependencies
            SET {$set}
            WHERE parent_id = ?
            AND id = ?
        ";
    }

    public static function wouldCreateCycle(): string
    {
        return "
            WITH RECURSIVE dependency_tree AS (
                SELECT
                    child_id,
                    CAST(child_id AS CHAR(1000)) AS path
                FROM dependencies
                WHERE parent_id = ?

                UNION ALL

                SELECT
                    d.child_id,
                    CONCAT(dt.path, ',', d.child_id)
                FROM dependencies d
                INNER JOIN dependency_tree dt ON d.parent_id = dt.child_id
                WHERE FIND_IN_SET(d.child_id, dt.path) = 0
            )
            SELECT 1
            FROM dependency_tree
            WHERE child_id = ?
            LIMIT 1
        ";
    }

    public static function existsSameRelation(): string
    {
        return "
            SELECT id
            FROM dependencies
            WHERE parent_id = ?
            AND child_id = ?
            AND relation_type = ?
            LIMIT 1
        ";
    }

    public static function findByIdForParent(): string
    {
        return "
            SELECT
                id,
                parent_id,
                child_id,
                relation_type,
                qty_required,
                uom,
                is_phantom,
                notes
            FROM dependencies
            WHERE parent_id = ?
            AND id = ?
            LIMIT 1
        ";
    }
}