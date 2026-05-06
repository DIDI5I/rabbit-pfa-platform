<?php

namespace App\Services\Chatbot\Presenters;

class ReorderRecommendationsPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'reorder_recommendations';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $raw = $toolResult['data']['recommendations'] ?? [];

        $items = $raw['items'] ?? $raw;
        $summary = $raw['summary'] ?? ($toolResult['data']['summary'] ?? []);

        if (!is_array($items)) {
            $items = [];
        }

        $recommendedItems = array_values(array_filter($items, function (array $item) {
            return (bool) ($item['recommendation'] ?? false);
        }));

        $previewItems = !empty($recommendedItems) ? $recommendedItems : $items;

        $total = $summary['recommended_count'] ?? count($recommendedItems);
        $shown = min(self::PREVIEW_LIMIT, count($previewItems));

        $answer = $total === 0
            ? 'There are no reorder recommendations right now.'
            : "Rabbit found {$total} reorder recommendation(s). Showing the first {$shown}.";

        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'recommended_count' => $summary['recommended_count'] ?? $total,
                'critical_count' => $summary['critical_count'] ?? 0,
                'high_count' => $summary['high_count'] ?? 0,
                'estimated_reorder_value' => $summary['estimated_reorder_value'] ?? null,
            ],
            $this->preview($previewItems, [
                'product_id',
                'name',
                'sku',
                'current_stock',
                'low_stock_threshold',
                'recommended_reorder_quantity',
                'priority',
                'confidence',
            ]),
            [
                'has_more' => count($previewItems) > $shown,
                'total' => count($previewItems),
                'shown' => $shown,
                'shown_this_response' => $shown,
                'period_days' => $toolResult['data']['parameters']['period_days'] ?? null,
                'forecast_days' => $toolResult['data']['parameters']['forecast_days'] ?? null,
                'filters' => $toolResult['data']['parameters']['filters'] ?? [],
            ],
            count($previewItems) > $shown
                ? ['Ask to see more reorder recommendations', 'Open stock intelligence page']
                : ['Open stock intelligence page']
        );
    }
}