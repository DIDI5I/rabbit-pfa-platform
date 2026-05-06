<?php

namespace App\Services\Chatbot\Presenters\StockIntelligenceDashboard;

use App\Services\Chatbot\Presenters\BaseResponsePresenter;

class StockIntelligenceDashboardExplanationPresenter extends BaseResponsePresenter
{
    protected const PREVIEW_LIMIT = 5;

    public function supports(string $tool): bool
    {
        return $tool === 'stock_intelligence_dashboard_explanation';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $summary = $toolResult['data']['summary'] ?? [];
        $engine = $toolResult['data']['engine'] ?? [];
        $critical = $toolResult['data']['critical_preview'] ?? [];
        $high = $toolResult['data']['high_preview'] ?? [];

        $totalProducts = (int) ($summary['total_products'] ?? 0);
        $recommendedCount = (int) ($summary['recommended_count'] ?? 0);
        $criticalCount = (int) ($summary['critical_count'] ?? 0);
        $highCount = (int) ($summary['high_count'] ?? 0);
        $estimateOnlyCount = (int) ($summary['estimate_only_count'] ?? 0);

        $byModel = is_array($summary['by_model'] ?? null) ? $summary['by_model'] : [];
        $byConfidence = is_array($summary['by_confidence'] ?? null) ? $summary['by_confidence'] : [];
        $dataQualityFlags = is_array($summary['data_quality_flags'] ?? null) ? $summary['data_quality_flags'] : [];

        $dominantModel = $this->dominantKey($byModel);
        $dominantConfidence = $this->dominantKey($byConfidence);
        $dominantFlag = $this->dominantKey($dataQualityFlags);

        if ($totalProducts === 0) {
            $answer = 'No stock intelligence dashboard data is currently available to explain.';
        } else {
            $answer = "The stock intelligence dashboard covers {$totalProducts} products. ";
            $answer .= "{$recommendedCount} product" . ($recommendedCount === 1 ? ' is' : 's are') . " recommended for reorder.";

            if ($criticalCount > 0 || $highCount > 0) {
                $answer .= " The urgent group contains {$criticalCount} critical and {$highCount} high-priority item" . (($criticalCount + $highCount) === 1 ? '' : 's') . ".";
            }

            if ($dominantModel !== null) {
                $answer .= " The most used model is {$dominantModel}, which usually means many products are being evaluated with that calculation path.";
            }

            if ($dominantConfidence !== null) {
                $answer .= " The most common confidence label is {$dominantConfidence}.";
            }

            if ($estimateOnlyCount > 0) {
                $answer .= " {$estimateOnlyCount} product" . ($estimateOnlyCount === 1 ? ' has' : 's have') . " ESTIMATE_ONLY confidence, usually because usable OUT movement history is missing or weak.";
            }

            if ($dominantFlag !== null) {
                $answer .= " The most common data-quality flag is {$dominantFlag}.";
            }
        }

        return $this->success(
            $answer,
            $toolResult,
            $role,
            $this->summary($summary, $engine),
            $this->explanationPreview($critical, $high),
            [
                'has_more' => false,
                'total_products' => $totalProducts,
                'recommended_count' => $recommendedCount,
                'critical_count' => $criticalCount,
                'high_count' => $highCount,
                'dominant_model' => $dominantModel,
                'dominant_confidence' => $dominantConfidence,
                'dominant_data_quality_flag' => $dominantFlag,
                'calculation_mode' => $engine['calculation_mode'] ?? null,
            ],
            [
                'Show stock intelligence summary',
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

    private function explanationPreview(array $critical, array $high): array
    {
        $preview = [];

        if (count($critical) > 0) {
            $preview[] = [
                'section' => 'critical_recommendations',
                'count' => count($critical),
                'items' => $this->itemPreview($critical, self::PREVIEW_LIMIT),
            ];
        }

        if (count($high) > 0) {
            $preview[] = [
                'section' => 'high_priority_recommendations',
                'count' => count($high),
                'items' => $this->itemPreview($high, self::PREVIEW_LIMIT),
            ];
        }

        return $preview;
    }

    private function itemPreview(array $items, int $limit): array
    {
        return array_map(function (array $item) {
            return [
                'product_id' => $item['product_id'] ?? null,
                'name' => $item['name'] ?? null,
                'sku' => $item['sku'] ?? null,
                'current_stock' => $item['current_stock'] ?? null,
                'reorder_point' => $item['reorder_point'] ?? null,
                'recommended_reorder_quantity' => $item['recommended_reorder_quantity'] ?? null,
                'priority' => $item['priority'] ?? null,
                'confidence' => $item['confidence'] ?? null,
                'selected_model' => $item['selected_model'] ?? null,
                'reason_codes' => $item['reason_codes'] ?? [],
                'data_quality_flags' => $item['data_quality_flags'] ?? [],
            ];
        }, array_slice($items, 0, $limit));
    }

    private function dominantKey(array $counts): ?string
    {
        if (empty($counts)) {
            return null;
        }

        arsort($counts);

        $key = array_key_first($counts);

        return is_string($key) ? $key : null;
    }
}