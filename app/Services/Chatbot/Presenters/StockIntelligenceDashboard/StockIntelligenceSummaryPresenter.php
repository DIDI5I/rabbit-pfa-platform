<?php

namespace App\Services\Chatbot\Presenters\StockIntelligenceDashboard;

use App\Services\Chatbot\Presenters\BaseResponsePresenter;

class StockIntelligenceSummaryPresenter extends BaseResponsePresenter
{
    protected const PREVIEW_LIMIT = 5;

    public function supports(string $tool): bool
    {
        return $tool === 'stock_intelligence_summary';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $summary = $toolResult['data']['summary'] ?? [];
        $preview = $toolResult['data']['recommended_preview'] ?? [];
        $engine = $toolResult['data']['engine'] ?? [];

        $totalProducts = (int) ($summary['total_products'] ?? 0);
        $recommendedCount = (int) ($summary['recommended_count'] ?? 0);
        $criticalCount = (int) ($summary['critical_count'] ?? 0);
        $highCount = (int) ($summary['high_count'] ?? 0);
        $estimatedValue = $summary['estimated_reorder_value'] ?? null;

        if ($totalProducts === 0) {
            $answer = 'No stock intelligence data is currently available.';
        } else {
            $answer = "Stock intelligence covers {$totalProducts} products. ";
            $answer .= "{$recommendedCount} product" . ($recommendedCount === 1 ? ' is' : 's are') . " recommended for reorder.";

            if ($criticalCount > 0 || $highCount > 0) {
                $answer .= " This includes {$criticalCount} critical and {$highCount} high-priority item" . (($criticalCount + $highCount) === 1 ? '' : 's') . ".";
            }

            if ($estimatedValue !== null) {
                $answer .= " Estimated reorder value is {$estimatedValue} MAD.";
            }
        }

        return $this->success(
            $answer,
            $toolResult,
            $role,
            $this->summary($summary, $engine),
            $this->recommendedPreview($preview, self::PREVIEW_LIMIT),
            [
                'has_more' => count($preview) >= self::PREVIEW_LIMIT && $recommendedCount > count($preview),
                'total_products' => $totalProducts,
                'recommended_count' => $recommendedCount,
                'critical_count' => $criticalCount,
                'high_count' => $highCount,
                'estimated_reorder_value' => $estimatedValue,
                'calculation_mode' => $engine['calculation_mode'] ?? null,
            ],
            [
                'Explain stock intelligence dashboard',
                'Show reorder recommendations',
                'Open stock intelligence dashboard',
            ]
        );
    }

    private function summary(array $summary, array $engine): array
    {
        return [
            'total_products' => $summary['total_products'] ?? 0,
            'recommended_count' => $summary['recommended_count'] ?? 0,
            'critical_count' => $summary['critical_count'] ?? 0,
            'high_count' => $summary['high_count'] ?? 0,
            'medium_count' => $summary['medium_count'] ?? 0,
            'low_count' => $summary['low_count'] ?? 0,
            'estimate_only_count' => $summary['estimate_only_count'] ?? 0,
            'estimated_reorder_value' => $summary['estimated_reorder_value'] ?? null,
            'by_model' => $summary['by_model'] ?? [],
            'by_confidence' => $summary['by_confidence'] ?? [],
            'by_priority' => $summary['by_priority'] ?? [],
            'data_quality_flags' => $summary['data_quality_flags'] ?? [],
            'period_days' => $engine['period_days'] ?? null,
            'forecast_days' => $engine['forecast_days'] ?? null,
            'calculation_mode' => $engine['calculation_mode'] ?? null,
        ];
    }

    private function recommendedPreview(array $items, int $limit): array
    {
        return array_map(function (array $item) {
            return [
                'product_id' => $item['product_id'] ?? null,
                'name' => $item['name'] ?? null,
                'sku' => $item['sku'] ?? null,
                'category' => $item['category'] ?? null,
                'current_stock' => $item['current_stock'] ?? null,
                'reorder_point' => $item['reorder_point'] ?? null,
                'recommended_reorder_quantity' => $item['recommended_reorder_quantity'] ?? null,
                'priority' => $item['priority'] ?? null,
                'confidence' => $item['confidence'] ?? null,
                'selected_model' => $item['selected_model'] ?? null,
                'reason_codes' => $item['reason_codes'] ?? [],
                'data_quality_flags' => $item['data_quality_flags'] ?? [],
                'estimated_reorder_value' => $item['estimated_reorder_value'] ?? null,
            ];
        }, array_slice($items, 0, $limit));
    }
}