<?php

namespace App\Services;

use App\Repositories\InventoryRepository;

class InventoryService
{
    public function __construct(
        private InventoryRepository $inventoryRepository
    ) {
    }

    public function all(): array
    {
        $items = $this->inventoryRepository->all();

        foreach ($items as &$item) {
            $item = $this->enrichInventoryItem($item);
        }

        unset($item);

        return $items;
    }

    public function alerts(): array
    {
        $items = $this->inventoryRepository->alerts();

        foreach ($items as &$item) {
            $item = $this->enrichInventoryItem($item);
            $item['recommended_action'] = 'CREATE_RFQ';
        }

        unset($item);

        return $items;
    }

    private function enrichInventoryItem(array $item): array
    {
        $item['stock_status'] = $this->resolveStockStatus(
            $item['current_stock'],
            $item['low_stock_threshold']
        );

        $item['estimated_stock_value'] = $this->calculateEstimatedStockValue(
            $item['current_stock'],
            $item['preferred_unit_cost_mad']
        );

        return $item;
    }

    private function resolveStockStatus(float $currentStock, float $lowStockThreshold): string
    {
        if ($currentStock <= 0) {
            return 'OUT_OF_STOCK';
        }

        if ($currentStock <= $lowStockThreshold) {
            return 'LOW_STOCK';
        }

        return 'OK';
    }

    private function calculateEstimatedStockValue(float $currentStock, ?float $unitCost): ?float
    {
        if ($unitCost === null) {
            return null;
        }

        return round($currentStock * $unitCost, 2);
    }
}