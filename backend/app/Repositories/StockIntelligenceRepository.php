<?php

namespace App\Repositories;

use App\Queries\StockIntelligenceQuery;

class StockIntelligenceRepository extends Repository
{
    public function baseProductRows(): array
    {
        return $this
            ->query(StockIntelligenceQuery::baseProductRows())
            ->fetchMany();
    }

    public function outflowSummary(int $periodDays): array
    {
        $rows = $this
            ->query(StockIntelligenceQuery::outflowSummary(), [$periodDays])
            ->fetchMany();

        $indexed = [];

        foreach ($rows as $row) {
            $productId = (int) $row['product_id'];

            $indexed[$productId] = [
                'product_id' => $productId,
                'out_movement_count' => (int) $row['out_movement_count'],
                'stock_out_quantity' => (float) $row['stock_out_quantity'],
                'last_out_at' => $row['last_out_at'],
            ];
        }

        return $indexed;
    }

    public function outflowHistory(int $periodDays): array
    {
        $rows = $this
            ->query(StockIntelligenceQuery::outflowHistory(), [$periodDays])
            ->fetchMany();

        $indexed = [];

        foreach ($rows as $row) {
            $productId = (int) $row['product_id'];

            if (!isset($indexed[$productId])) {
                $indexed[$productId] = [];
            }

            $indexed[$productId][] = [
                'quantity' => (float) $row['quantity'],
                'created_at' => $row['created_at'],
            ];
        }

        return $indexed;
    }
}