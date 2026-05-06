<?php

namespace App\Services\StockIntelligence\ForecastModels;

class MovingAverageModel extends ForecastModel
{
    public function name(): string
    {
        return 'moving_average';
    }

    public function isEligible(array $context): bool
    {
        return $this->outMovementCount($context) >= 3;
    }

    public function estimateDailyOutflow(array $context): ?float
    {
        $qty = $this->stockOutQuantity($context);

        return $qty > 0
            ? round($qty / $this->periodDays($context), 4)
            : null;
    }

    public function eligibilityReason(array $context): array
    {
        return [
            'model' => $this->name(),
            'reason_code' => 'HAS_USABLE_OUT_HISTORY',
            'details' => [
                'out_movement_count' => $this->outMovementCount($context),
                'minimum_required' => 3,
                'role' => 'usable_history_fallback',
            ],
        ];
    }

    public function ineligibilityReason(array $context): array
    {
        return [
            'model' => $this->name(),
            'reason_code' => $this->outMovementCount($context) < 3
                ? 'REQUIRES_AT_LEAST_3_OUT_MOVEMENTS'
                : 'MORE_SPECIFIC_MODEL_SELECTED',
            'details' => [
                'out_movement_count' => $this->outMovementCount($context),
                'minimum_required' => 3,
            ],
        ];
    }
}