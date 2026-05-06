<?php

namespace App\Services\StockIntelligence\ForecastModels;

class SeasonalityAnalysisModel extends ForecastModel
{
    public function name(): string
    {
        return 'seasonal_index';
    }

    public function isEligible(array $context): bool
    {
        $analysis = $this->analyze($context);

        return $analysis['enabled'] === true
            && $analysis['status'] === 'ANALYZED'
            && $analysis['seasonality_detected'] === true;
    }

    public function estimateDailyOutflow(array $context): ?float
    {
        /*
         * Diagnostic only in V1.
         * Do not use seasonality to forecast reorder quantity yet.
         */
        return null;
    }

    public function eligibilityReason(array $context): array
    {
        return [
            'model' => $this->name(),
            'reason_code' => 'SEASONAL_PATTERN_CANDIDATE',
            'details' => $this->analyze($context),
        ];
    }

    public function ineligibilityReason(array $context): array
    {
        $analysis = $this->analyze($context);

        $reasonCode = match ($analysis['status']) {
            'INSUFFICIENT_MONTHLY_BUCKETS' => 'REQUIRES_AT_LEAST_12_MONTHLY_BUCKETS',
            'INSUFFICIENT_NON_ZERO_MONTHS' => 'REQUIRES_AT_LEAST_4_NON_ZERO_MONTHS',
            'NO_SEASONALITY_DETECTED' => 'NO_REPEATING_SEASONAL_PATTERN_DETECTED',
            default => 'SEASONALITY_ANALYSIS_NOT_ELIGIBLE',
        };

        return [
            'model' => $this->name(),
            'reason_code' => $reasonCode,
            'details' => $analysis,
        ];
    }

    public function analyze(array $context): array
    {
        $monthlyBuckets = $this->monthlyBuckets($context);
        $bucketCount = count($monthlyBuckets);

        if ($bucketCount < 12) {
            return $this->emptyAnalysis(
                'INSUFFICIENT_MONTHLY_BUCKETS',
                $monthlyBuckets
            );
        }

        $nonZeroCount = count(array_filter(
            $monthlyBuckets,
            fn ($qty) => (float) $qty > 0
        ));

        if ($nonZeroCount < 4) {
            return $this->emptyAnalysis(
                'INSUFFICIENT_NON_ZERO_MONTHS',
                $monthlyBuckets
            );
        }

        $values = array_values($monthlyBuckets);
        $averageMonthlyDemand = array_sum($values) / max(1, count($values));

        if ($averageMonthlyDemand <= 0) {
            return $this->emptyAnalysis(
                'INSUFFICIENT_NON_ZERO_MONTHS',
                $monthlyBuckets
            );
        }

        $seasonalIndexes = [];

        foreach ($monthlyBuckets as $month => $qty) {
            $seasonalIndexes[$month] = round($qty / $averageMonthlyDemand, 4);
        }

        $peakMonth = null;
        $peakIndex = null;

        foreach ($seasonalIndexes as $month => $index) {
            if ($peakIndex === null || $index > $peakIndex) {
                $peakMonth = $month;
                $peakIndex = $index;
            }
        }

        /*
         * Conservative V1 rule:
         * A month is considered a possible seasonal peak only if it is at least
         * 1.5x the average monthly demand.
         */
        $seasonalityDetected = $peakIndex !== null && $peakIndex >= 1.5;

        return [
            'enabled' => true,
            'status' => $seasonalityDetected
                ? 'ANALYZED'
                : 'NO_SEASONALITY_DETECTED',
            'method' => 'monthly_seasonal_index',
            'bucket_type' => 'monthly',
            'bucket_count' => $bucketCount,
            'non_zero_bucket_count' => $nonZeroCount,
            'minimum_monthly_buckets_required' => 12,
            'preferred_monthly_buckets' => 24,
            'average_monthly_demand' => round($averageMonthlyDemand, 4),
            'seasonality_detected' => $seasonalityDetected,
            'peak_month' => $peakMonth,
            'peak_index' => $peakIndex,
            'seasonality_threshold' => 1.5,
            'monthly_buckets' => $monthlyBuckets,
            'seasonal_indexes' => $seasonalIndexes,
        ];
    }

    private function emptyAnalysis(string $status, array $monthlyBuckets): array
    {
        return [
            'enabled' => true,
            'status' => $status,
            'method' => 'monthly_seasonal_index',
            'bucket_type' => 'monthly',
            'bucket_count' => count($monthlyBuckets),
            'non_zero_bucket_count' => count(array_filter(
                $monthlyBuckets,
                fn ($qty) => (float) $qty > 0
            )),
            'minimum_monthly_buckets_required' => 12,
            'preferred_monthly_buckets' => 24,
            'average_monthly_demand' => null,
            'seasonality_detected' => false,
            'peak_month' => null,
            'peak_index' => null,
            'seasonality_threshold' => 1.5,
            'monthly_buckets' => $monthlyBuckets,
            'seasonal_indexes' => [],
        ];
    }

    private function monthlyBuckets(array $context): array
    {
        $history = $context['history'] ?? [];
        $periodDays = $this->periodDays($context);

        $endDate = new \DateTimeImmutable('first day of this month');
        $startDate = $endDate->modify("-{$periodDays} days")->modify('first day of this month');

        $buckets = [];

        $cursor = $startDate;

        while ($cursor <= $endDate) {
            $bucket = $cursor->format('Y-m');
            $buckets[$bucket] = 0.0;
            $cursor = $cursor->modify('+1 month');
        }

        foreach ($history as $movement) {
            $date = new \DateTimeImmutable((string) $movement['created_at']);

            if ($date < $startDate || $date > $endDate->modify('+1 month')) {
                continue;
            }

            $bucket = $date->format('Y-m');

            if (!isset($buckets[$bucket])) {
                $buckets[$bucket] = 0.0;
            }

            $buckets[$bucket] += (float) $movement['quantity'];
        }

        ksort($buckets);

        return $buckets;
    }
}