<?php

namespace App\Services\Chatbot\Tools;

use App\Repositories\InventoryRepository;
use App\Services\InventoryService;

class InventorySummaryTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'inventory_summary';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';

        $service = new InventoryService(new InventoryRepository());
        $items = $service->all();

        $count = count($items);

        return $this->result('inventory_summary', $count === 0 ? 'empty' : 'success', [
            'items' => $items,
            'count' => $count,
            'summary' => $this->inventorySummaryCounts($items),
        ], $this->meta('inventory_summary', $role, 'read_only', true, $count));
    }

    private function inventorySummaryCounts(array $items): array
    {
        return [
            'total_items' => count($items),
            'ok_count' => count(array_filter($items, fn ($item) => ($item['stock_status'] ?? null) === 'OK')),
            'low_stock_count' => count(array_filter($items, fn ($item) => ($item['stock_status'] ?? null) === 'LOW_STOCK')),
            'out_of_stock_count' => count(array_filter($items, fn ($item) => ($item['stock_status'] ?? null) === 'OUT_OF_STOCK')),
            'total_estimated_stock_value_mad' => array_sum(array_map(
                fn ($item) => (float) ($item['estimated_stock_value_mad'] ?? $item['estimated_stock_value'] ?? 0),
                $items
            )),
        ];
    }
}