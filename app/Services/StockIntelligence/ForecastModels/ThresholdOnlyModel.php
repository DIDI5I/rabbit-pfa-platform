<?php

namespace App\Services\StockIntelligence\ForecastModels;

class ThresholdOnlyModel extends ForecastModel
{
    public function name(): string
    {
        return 'threshold_only';
    }

    public function isEligible(array $context): bool
    {
        return $this->outMovementCount($context) < 3;
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
            'reason_code' => $this->outMovementCount($context) === 0
                ? 'NO_OUT_HISTORY'
                : 'LIMITED_OUT_HISTORY',
            'details' => [
                'out_movement_count' => $this->outMovementCount($context),
                'maximum_for_threshold_only' => 2,
            ],
        ];
    }

    public function ineligibilityReason(array $context): array
    {
        return [
            'model' => $this->name(),
            'reason_code' => $this->outMovementCount($context) > 2
                ? 'MORE_DEMAND_HISTORY_AVAILABLE'
                : 'MORE_SPECIFIC_MODEL_SELECTED',
            'details' => [
                'out_movement_count' => $this->outMovementCount($context),
                'threshold_only_max_out_movements' => 2,
            ],
        ];
    }
}