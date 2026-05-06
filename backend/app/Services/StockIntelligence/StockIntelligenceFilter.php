<?php

namespace App\Services\StockIntelligence;

class StockIntelligenceFilter
{
    public function apply(array $items, array $filters): array
    {
        $filters = $this->normalize($filters);

        return array_values(array_filter($items, function (array $item) use ($filters): bool {
            if ($filters['only_recommended'] === true && $item['recommendation'] !== true) {
                return false;
            }

            if ($filters['priority'] !== null && $item['priority'] !== $filters['priority']) {
                return false;
            }

            if ($filters['confidence'] !== null && $item['confidence'] !== $filters['confidence']) {
                return false;
            }

            if ($filters['model'] !== null && $item['selected_model'] !== $filters['model']) {
                return false;
            }

            return true;
        }));
    }

    public function normalize(array $filters): array
    {
        return [
            'only_recommended' => $this->parseBoolFilter($filters['only_recommended'] ?? null),
            'priority' => $this->normalizeEnumFilter(
                $filters['priority'] ?? null,
                ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW', 'NONE']
            ),
            'confidence' => $this->normalizeEnumFilter(
                $filters['confidence'] ?? null,
                ['HIGH', 'MEDIUM', 'LOW', 'ESTIMATE_ONLY']
            ),
            'model' => $this->normalizeEnumFilter(
                $filters['model'] ?? null,
                [
                    'criticality_only',
                    'threshold_only',
                    'moving_average',
                    'simple_exponential_smoothing',
                    'croston_sba',
                    'seasonal_index',
                    'regression_trend',
                ]
            ),
            'include_diagnostics' => $this->parseBoolFilter($filters['include_diagnostics'] ?? null),
        ];
    }

    private function parseBoolFilter(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        return match ($value) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => null,
        };
    }

    private function normalizeEnumFilter(mixed $value, array $allowed): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        return in_array($value, $allowed, true)
            ? $value
            : null;
    }
}