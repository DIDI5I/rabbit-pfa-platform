<?php

namespace App\Repositories;

use App\Queries\DashboardStockQuery;

class DashboardStockRepository extends Repository
{
    public function generalProductCounts(): array
    {
        $row = $this
            ->query(DashboardStockQuery::generalProductCounts())
            ->fetchOne();

        return [
            'total_products' => (int) ($row['total_products'] ?? 0),
            'active_products' => (int) ($row['active_products'] ?? 0),
        ];
    }

    public function inventoryStatusCounts(): array
    {
        $row = $this
            ->query(DashboardStockQuery::inventoryStatusCounts())
            ->fetchOne();

        return [
            'out_of_stock_count' => (int) ($row['out_of_stock_count'] ?? 0),
            'low_stock_count' => (int) ($row['low_stock_count'] ?? 0),
            'stock_ok_count' => (int) ($row['stock_ok_count'] ?? 0),
        ];
    }

    public function stockMovementCounts(): array
    {
        $row = $this
            ->query(DashboardStockQuery::stockMovementCounts())
            ->fetchOne();

        return [
            'total_stock_movements' => (int) ($row['total_stock_movements'] ?? 0),
            'stock_in_movements' => (int) ($row['stock_in_movements'] ?? 0),
            'stock_out_movements' => (int) ($row['stock_out_movements'] ?? 0),
            'stock_movements_this_month' => (int) ($row['stock_movements_this_month'] ?? 0),
            'stock_in_movements_this_month' => (int) ($row['stock_in_movements_this_month'] ?? 0),
            'stock_out_movements_this_month' => (int) ($row['stock_out_movements_this_month'] ?? 0),
        ];
    }

    public function purchaseLotStatusCounts(): array
    {
        $row = $this
            ->query(DashboardStockQuery::purchaseLotStatusCounts())
            ->fetchOne();

        return [
            'total_purchase_lots' => (int) ($row['total_purchase_lots'] ?? 0),
            'draft_purchase_lots' => (int) ($row['draft_purchase_lots'] ?? 0),
            'finalized_purchase_lots' => (int) ($row['finalized_purchase_lots'] ?? 0),
            'cancelled_purchase_lots' => (int) ($row['cancelled_purchase_lots'] ?? 0),
        ];
    }

    public function totalInventoryValue(): float
    {
        $row = $this
            ->query(DashboardStockQuery::totalInventoryValue())
            ->fetchOne();

        return round((float) ($row['total_inventory_value'] ?? 0), 2);
    }

    public function productsPerformance(): array
    {
        $rows = $this
            ->query(DashboardStockQuery::productsPerformance())
            ->fetchMany();

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['low_stock_threshold'] = (float) $row['low_stock_threshold'];

            $row['current_stock'] = (float) $row['current_stock'];
            $row['stock_in_quantity'] = (float) $row['stock_in_quantity'];
            $row['stock_out_quantity'] = (float) $row['stock_out_quantity'];

            $row['movement_count'] = (int) $row['movement_count'];
            $row['latest_unit_purchase_cost'] = (float) $row['latest_unit_purchase_cost'];
        }

        unset($row);

        return $rows;
    }

    public function productPerformanceById(int $productId): ?array
    {
        $row = $this
            ->query(DashboardStockQuery::productPerformanceById(), [$productId])
            ->fetchOne();

        if (!$row) {
            return null;
        }

        return $this->castProductPerformanceRow($row);
    }

    public function productMovementTimeline(int $productId): array
    {
        $rows = $this
            ->query(DashboardStockQuery::productMovementTimeline(), [$productId])
            ->fetchMany();

        foreach ($rows as &$row) {
            $row['stock_in'] = (float) $row['stock_in'];
            $row['stock_out'] = (float) $row['stock_out'];
            $row['net_change'] = (float) $row['net_change'];
        }

        unset($row);

        return $rows;
    }

    public function productCostHistory(int $productId): array
    {
        $rows = $this
            ->query(DashboardStockQuery::productCostHistory(), [$productId])
            ->fetchMany();

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];

            $row['quantity_received'] = (float) $row['quantity_received'];
            $row['supplier_unit_price'] = (float) $row['supplier_unit_price'];
            $row['supplier_total_price'] = (float) $row['supplier_total_price'];

            $row['transport_cost'] = (float) $row['transport_cost'];
            $row['customs_cost'] = (float) $row['customs_cost'];
            $row['handling_cost'] = (float) $row['handling_cost'];
            $row['packaging_cost'] = (float) $row['packaging_cost'];
            $row['order_preparation_cost'] = (float) $row['order_preparation_cost'];
            $row['other_cost'] = (float) $row['other_cost'];

            $row['total_purchase_cost'] = (float) $row['total_purchase_cost'];
            $row['unit_purchase_cost'] = (float) $row['unit_purchase_cost'];

            $row['reference_id'] = $row['reference_id'] !== null
                ? (int) $row['reference_id']
                : null;
        }

        unset($row);

        return $rows;
    }

    private function castProductPerformanceRow(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['low_stock_threshold'] = (float) $row['low_stock_threshold'];

        $row['current_stock'] = (float) $row['current_stock'];
        $row['stock_in_quantity'] = (float) $row['stock_in_quantity'];
        $row['stock_out_quantity'] = (float) $row['stock_out_quantity'];

        $row['movement_count'] = (int) $row['movement_count'];
        $row['latest_unit_purchase_cost'] = (float) $row['latest_unit_purchase_cost'];
        $row['last_movement_at'] = $row['last_movement_at'] ?? null;

        return $row;
    }

    public function abcBaseProducts(): array
    {
        $rows = $this
            ->query(DashboardStockQuery::abcBaseProducts())
            ->fetchMany();

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['low_stock_threshold'] = (float) $row['low_stock_threshold'];

            $row['current_stock'] = (float) $row['current_stock'];
            $row['stock_in_quantity'] = (float) $row['stock_in_quantity'];
            $row['stock_out_quantity'] = (float) $row['stock_out_quantity'];

            $row['movement_count'] = (int) $row['movement_count'];
            $row['latest_unit_purchase_cost'] = (float) $row['latest_unit_purchase_cost'];
            $row['last_movement_at'] = $row['last_movement_at'] ?? null;
        }

        unset($row);

        return $rows;
    }
}