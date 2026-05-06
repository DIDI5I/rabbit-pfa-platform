<?php

namespace App\Services\StockIntelligence;

class StockIntelligenceSummaryBuilder
{
    public function build(array $items): array
    {
        return [
            'total_products' => count($items),
            'recommended_count' => count(array_filter($items, fn ($item) => $item['recommendation'] === true)),
            'critical_count' => count(array_filter($items, fn ($item) => $item['priority'] === 'CRITICAL')),
            'high_count' => count(array_filter($items, fn ($item) => $item['priority'] === 'HIGH')),
            'medium_count' => count(array_filter($items, fn ($item) => $item['priority'] === 'MEDIUM')),
            'low_count' => count(array_filter($items, fn ($item) => $item['priority'] === 'LOW')),
            'estimate_only_count' => count(array_filter($items, fn ($item) => $item['confidence'] === 'ESTIMATE_ONLY')),
            'estimated_reorder_value' => round(array_sum(array_map(
                fn ($item) => (float) ($item['estimated_reorder_value'] ?? 0),
                $items
            )), 2),
            'by_model' => $this->countBy($items, 'selected_model'),
            'by_confidence' => $this->countBy($items, 'confidence'),
            'by_priority' => $this->countBy($items, 'priority'),
            'data_quality_flags' => $this->countFlags($items, 'data_quality_flags'),
        ];
    }

    private function countBy(array $items, string $field): array
    {
        $counts = [];

        foreach ($items as $item) {
            $value = $item[$field] ?? 'UNKNOWN';

            if (!isset($counts[$value])) {
                $counts[$value] = 0;
            }

            $counts[$value]++;
        }

        ksort($counts);

        return $counts;
    }

    private function countFlags(array $items, string $field): array
    {
        $counts = [];

        foreach ($items as $item) {
            foreach (($item[$field] ?? []) as $flag) {
                if (!isset($counts[$flag])) {
                    $counts[$flag] = 0;
                }

                $counts[$flag]++;
            }
        }

        ksort($counts);

        return $counts;
    }
}