<?php

namespace App\Services\Chatbot\Presenters;

class InventoryAlertsPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'inventory_alerts';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $alerts = $toolResult['data']['alerts'] ?? [];
        $summary = $toolResult['data']['summary'] ?? [];

        $total = $summary['total_alerts'] ?? count($alerts);
        $low = $summary['low_stock_count'] ?? 0;
        $out = $summary['out_of_stock_count'] ?? 0;
        $shown = min(self::PREVIEW_LIMIT, count($alerts));

        $answer = $total === 0
            ? 'There are no inventory alerts right now.'
            : "There are {$total} inventory alert(s): {$out} out of stock and {$low} low stock.";

        return $this->success(
            $answer,
            $toolResult,
            $role,
            $summary,
            $this->preview($alerts, [
                'id',
                'name',
                'sku',
                'current_stock',
                'low_stock_threshold',
                'stock_status',
                'recommended_action',
            ]),
            [
                'has_more' => $total > $shown,
                'total' => $total,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $total > $shown
                ? ['Ask to see more alerts', 'Open inventory page', 'Ask what should be reordered']
                : ['Open inventory page', 'Ask what should be reordered']
        );
    }
}