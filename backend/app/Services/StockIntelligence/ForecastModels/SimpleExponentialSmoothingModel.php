<?php

namespace App\Services\StockIntelligence\ForecastModels;

class SimpleExponentialSmoothingModel extends ForecastModel
{
    private const ALPHA = 0.2;

    public function name(): string
    {
        return 'simple_exponential_smoothing';
    }

    public function isEligible(array $context): bool
    {
        return $this->outMovementCount($context) >= 10
            && !$this->hasOutlier($context);
    }

    public function estimateDailyOutflow(array $context): ?float
    {
        $history = $context['history'] ?? [];

        if (empty($history)) {
            return null;
        }

        $dailyBuckets = [];

        foreach ($history as $movement) {
            $date = substr((string) $movement['created_at'], 0, 10);

            if (!isset($dailyBuckets[$date])) {
                $dailyBuckets[$date] = 0.0;
            }

            $dailyBuckets[$date] += (float) $movement['quantity'];
        }

        ksort($dailyBuckets);

        $values = array_values($dailyBuckets);

        if (empty($values)) {
            return null;
        }

        $forecast = (float) $values[0];

        foreach (array_slice($values, 1) as $actual) {
            $forecast = $forecast + self::ALPHA * ((float) $actual - $forecast);
        }

        return round($forecast / max(1, $this->periodDays($context) / count($values)), 4);
    }

    public function eligibilityReason(array $context): array
    {
        return [
            'model' => $this->name(),
            'reason_code' => 'HAS_FREQUENT_OUT_HISTORY',
            'details' => [
                'out_movement_count' => $this->outMovementCount($context),
                'minimum_required' => 10,
                'alpha' => self::ALPHA,
            ],
        ];
    }

    public function ineligibilityReason(array $context): array
    {
        $reasonCode = $this->outMovementCount($context) < 10
            ? 'REQUIRES_AT_LEAST_10_OUT_MOVEMENTS'
            : 'NOT_SELECTED_BY_CURRENT_RULES';

        if ($this->hasOutlier($context)) {
            $reasonCode = 'OUTLIER_DETECTED_REDUCES_SMOOTHING_CONFIDENCE';
        }

        return [
            'model' => $this->name(),
            'reason_code' => $reasonCode,
            'details' => [
                'out_movement_count' => $this->outMovementCount($context),
                'minimum_required' => 10,
                'outlier_detected' => $this->hasOutlier($context),
            ],
        ];
    }
}