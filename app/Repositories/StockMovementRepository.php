<?php

namespace App\Repositories;

use App\Queries\StockMovementQuery;

class StockMovementRepository extends Repository
{
    public function create(array $data): void
    {
        $this->query(
            StockMovementQuery::insert(),
            [
                $data['component_id'],
                $data['type'],
                $data['quantity'],
                $data['reason'],
                $data['reference_type'] ?? null,
                $data['reference_id'] ?? null,
                $data['notes'] ?? null,
                $data['created_by'] ?? null,
            ]
        );
    }

    public function currentStock(int $componentId): float
    {
        $row = $this
            ->query(StockMovementQuery::currentStock(), [$componentId])
            ->fetchOne();

        return (float) ($row['current_stock'] ?? 0);
    }

    public function findByComponent(int $componentId): array
    {
        $rows = $this
            ->query(StockMovementQuery::findByComponent(), [$componentId])
            ->fetchMany();

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['component_id'] = (int) $row['component_id'];
            $row['quantity'] = (float) $row['quantity'];
            $row['reference_id'] = isset($row['reference_id']) ? (int) $row['reference_id'] : null;
            $row['created_by'] = isset($row['created_by']) ? (int) $row['created_by'] : null;
        }

        unset($row);

        return $rows;
    }

    public function stockSummary(int $componentId): ?array
    {
        $row = $this
            ->query(StockMovementQuery::stockSummary(), [$componentId])
            ->fetchOne();

        if (!$row) {
            return null;
        }

        $row['id'] = (int) $row['id'];
        $row['legacy_stock_qty'] = (float) $row['legacy_stock_qty'];
        $row['low_stock_threshold'] = (float) $row['low_stock_threshold'];
        $row['current_stock'] = (float) $row['current_stock'];

        return $row;
    }

    public function existsByReferenceAndReason(
        string $referenceType,
        int $referenceId,
        string $reason
    ): bool {
        return $this
            ->query(
                StockMovementQuery::existsByReferenceAndReason(),
                [$referenceType, $referenceId, $reason]
            )
            ->exists();
    }
}