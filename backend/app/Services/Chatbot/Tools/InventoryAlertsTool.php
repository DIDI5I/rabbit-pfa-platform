<?php

namespace App\Services\Chatbot\Tools;

use App\Repositories\InventoryRepository;
use App\Services\InventoryService;

class InventoryAlertsTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'inventory_alerts';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';

        $service = new InventoryService(new InventoryRepository());
        $items = $service->alerts();

        $count = count($items);

        return $this->result('inventory_alerts', $count === 0 ? 'empty' : 'success', [
            'alerts' => $items,
            'count' => $count,
            'summary' => [
                'total_alerts' => $count,
                'out_of_stock_count' => count(array_filter($items, fn ($item) => ($item['stock_status'] ?? null) === 'OUT_OF_STOCK')),
                'low_stock_count' => count(array_filter($items, fn ($item) => ($item['stock_status'] ?? null) === 'LOW_STOCK')),
            ],
        ], $this->meta('inventory_alerts', $role, 'read_only', true, $count));
    }
}