<?php

namespace App\Repositories;

use App\Queries\InventoryQuery;

class InventoryRepository extends Repository
{
    public function all(): array
    {
        $rows = $this
            ->query(InventoryQuery::all())
            ->fetchMany();

        return $this->castRows($rows);
    }

    public function alerts(): array
    {
        $rows = $this
            ->query(InventoryQuery::alerts())
            ->fetchMany();

        return $this->castRows($rows);
    }

    private function castRows(array $rows): array
    {
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['legacy_stock_qty'] = (float) $row['legacy_stock_qty'];
            $row['low_stock_threshold'] = (float) $row['low_stock_threshold'];
            $row['current_stock'] = (float) $row['current_stock'];

            $row['preferred_unit_cost_mad'] = $row['preferred_unit_cost_mad'] !== null
                ? (float) $row['preferred_unit_cost_mad']
                : null;

            $row['lead_time_days'] = $row['lead_time_days'] !== null
                ? (int) $row['lead_time_days']
                : null;
        }

        unset($row);

        return $rows;
    }
}