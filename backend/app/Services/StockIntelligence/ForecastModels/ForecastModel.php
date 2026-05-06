<?php

namespace App\Services\StockIntelligence\ForecastModels;

abstract class ForecastModel
{
    abstract public function name(): string;

    abstract public function isEligible(array $context): bool;

    abstract public function estimateDailyOutflow(array $context): ?float;

    abstract public function eligibilityReason(array $context): array;

    abstract public function ineligibilityReason(array $context): array;

    protected function outMovementCount(array $context): int
    {
        return (int) ($context['out_movement_count'] ?? 0);
    }

    protected function stockOutQuantity(array $context): float
    {
        return (float) ($context['stock_out_quantity'] ?? 0);
    }

    protected function periodDays(array $context): int
    {
        return max(1, (int) ($context['period_days'] ?? 90));
    }

    protected function vedClass(array $context): ?string
    {
        return $context['ved_class'] ?? null;
    }

    protected function hasOutlier(array $context): bool
    {
        return (bool) ($context['outlier_analysis']['has_outlier'] ?? false);
    }
}