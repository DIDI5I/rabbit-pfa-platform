<?php

namespace App\Services\Chatbot\Presenters;

class InventorySummaryPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'inventory_summary';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $items = $toolResult['data']['items'] ?? [];
        $summary = $toolResult['data']['summary'] ?? [];

        $total = $summary['total_items'] ?? count($items);
        $low = $summary['low_stock_count'] ?? 0;
        $out = $summary['out_of_stock_count'] ?? 0;
        $shown = min(self::PREVIEW_LIMIT, count($items));

        $answer = "Inventory has {$total} item(s): {$low} low stock and {$out} out of stock.";

        return $this->success(
            $answer,
            $toolResult,
            $role,
            $summary,
            $this->preview($items, [
                'id',
                'name',
                'sku',
                'category',
                'current_stock',
                'low_stock_threshold',
                'stock_status',
            ]),
            [
                'has_more' => $total > $shown,
                'total' => $total,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $total > $shown
                ? ['Ask to see more inventory items', 'Open inventory page']
                : ['Open inventory page']
        );
    }
}