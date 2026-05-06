<?php

namespace App\Repositories;

class CostRollupRepository extends Repository
{
    public function calculate(int $productId): array
    {
        $sql = "
            WITH RECURSIVE bom AS (
                SELECT
                    c.id,
                    CAST(1.0 AS DECIMAL(18,4)) AS extended_qty,
                    0 AS depth,
                    CAST(c.id AS CHAR(500)) AS path
                FROM components c
                WHERE c.id = ?

                UNION ALL

                SELECT
                    c.id,
                    CAST(bom.extended_qty * d.qty_required AS DECIMAL(18,4)) AS extended_qty,
                    bom.depth + 1,
                    CONCAT(bom.path, ',', c.id)
                FROM dependencies d
                JOIN components c ON d.child_id = c.id
                JOIN bom ON d.parent_id = bom.id
                WHERE FIND_IN_SET(c.id, bom.path) = 0
                  AND bom.depth < 10
                  AND d.is_phantom = FALSE
            )
            SELECT
                COALESCE(SUM(b.extended_qty * ps.unit_cost), 0) AS total_material_cost,
                COUNT(DISTINCT b.id) AS unique_components
            FROM bom b
            JOIN part_sources ps
                ON ps.component_id = b.id
               AND ps.is_preferred = TRUE
            WHERE b.depth > 0
        ";

        $row = $this
            ->query($sql, [$productId])
            ->fetchOne();

        return [
            'product_id' => $productId,
            'total_material_cost' => (float) ($row['total_material_cost'] ?? 0),
            'unique_components' => (int) ($row['unique_components'] ?? 0),
        ];
    }
}