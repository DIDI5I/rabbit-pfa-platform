<?php

namespace App\Services\StockIntelligence\ForecastModels;

class CriticalityOnlyModel extends ForecastModel
{
    public function name(): string
    {
        return 'criticality_only';
    }

    public function isEligible(array $context): bool
    {
        return $this->outMovementCount($context) === 0
            && $this->vedClass($context) === 'V';
    }

    public function estimateDailyOutflow(array $context): ?float
    {
        return null;
    }

    public function eligibilityReason(array $context): array
    {
        return [
            'model' => $this->name(),
            'reason_code' => 'VED_VITAL_WITH_NO_OUT_HISTORY',
            'details' => [
                'ved_class' => $this->vedClass($context),
                'out_movement_count' => $this->outMovementCount($context),
            ],
        ];
    }

    public function ineligibilityReason(array $context): array
    {
        return [
            'model' => $this->name(),
            'reason_code' => $this->vedClass($context) === 'V'
                ? 'OUT_HISTORY_OR_STOCK_CONTEXT_ALLOWED_OTHER_MODEL'
                : 'REQUIRES_VED_VITAL_WITH_NO_OUT_HISTORY',
            'details' => [
                'ved_class' => $this->vedClass($context),
                'out_movement_count' => $this->outMovementCount($context),
            ],
        ];
    }
}