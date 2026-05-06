<?php

namespace App\Services\StockIntelligence\ForecastModels;

class CrostonSbaModel extends ForecastModel
{
    private const ALPHA = 0.2;

    public function name(): string
    {
        return 'croston_sba';
    }

    public function isEligible(array $context): bool
    {
        $profile = $this->intermittentDemandProfile($context);

        return $profile['bucket_count'] >= 12
            && $profile['non_zero_bucket_count'] >= 4
            && $profile['zero_bucket_ratio'] >= 0.5
            && !$this->hasOutlier($context);
    }

    public function estimateDailyOutflow(array $context): ?float
    {
        $buckets = $this->weeklyBuckets($context);

        if (count($buckets) < 12) {
            return null;
        }

        $demandSizes = [];
        $intervals = [];

        $weeksSinceLastDemand = 0;

        foreach ($buckets as $qty) {
            $weeksSinceLastDemand++;

            if ($qty > 0) {
                $demandSizes[] = (float) $qty;
                $intervals[] = $weeksSinceLastDemand;
                $weeksSinceLastDemand = 0;
            }
        }

        if (count($demandSizes) < 4 || count($intervals) < 4) {
            return null;
        }

        $smoothedDemand = $demandSizes[0];
        $smoothedInterval = $intervals[0];

        for ($i = 1; $i < count($demandSizes); $i++) {
            $smoothedDemand = $smoothedDemand + self::ALPHA * ($demandSizes[$i] - $smoothedDemand);
            $smoothedInterval = $smoothedInterval + self::ALPHA * ($intervals[$i] - $smoothedInterval);
        }

        if ($smoothedInterval <= 0) {
            return null;
        }

        /*
         * SBA correction:
         * Croston forecast = demand / interval
         * SBA forecast = Croston forecast * (1 - alpha / 2)
         */
        $weeklyForecast = ($smoothedDemand / $smoothedInterval) * (1 - self::ALPHA / 2);

        return round($weeklyForecast / 7, 4);
    }

    public function eligibilityReason(array $context): array
    {
        return [
            'model' => $this->name(),
            'reason_code' => 'INTERMITTENT_DEMAND_PROFILE_CONFIRMED',
            'details' => array_merge(
                $this->intermittentDemandProfile($context),
                [
                    'alpha' => self::ALPHA,
                    'method' => 'croston_sba_weekly_buckets',
                ]
            ),
        ];
    }

    public function ineligibilityReason(array $context): array
    {
        $profile = $this->intermittentDemandProfile($context);

        $reasonCode = 'INTERMITTENT_PROFILE_NOT_CONFIRMED';

        if ($profile['bucket_count'] < 12) {
            $reasonCode = 'REQUIRES_AT_LEAST_12_WEEKLY_BUCKETS';
        } elseif ($profile['non_zero_bucket_count'] < 4) {
            $reasonCode = 'REQUIRES_AT_LEAST_4_NON_ZERO_DEMAND_BUCKETS';
        } elseif ($profile['zero_bucket_ratio'] < 0.5) {
            $reasonCode = 'ZERO_DEMAND_RATIO_TOO_LOW_FOR_CROSTON';
        } elseif ($this->hasOutlier($context)) {
            $reasonCode = 'OUTLIER_DETECTED_REDUCES_CROSTON_CONFIDENCE';
        }

        return [
            'model' => $this->name(),
            'reason_code' => $reasonCode,
            'details' => $profile,
        ];
    }

    private function intermittentDemandProfile(array $context): array
    {
        $buckets = $this->weeklyBuckets($context);

        $bucketCount = count($buckets);

        if ($bucketCount === 0) {
            return [
                'bucket_type' => 'weekly',
                'bucket_count' => 0,
                'non_zero_bucket_count' => 0,
                'zero_bucket_count' => 0,
                'zero_bucket_ratio' => 0,
                'is_intermittent_candidate' => false,
            ];
        }

        $nonZeroBucketCount = count(array_filter(
            $buckets,
            fn ($qty) => (float) $qty > 0
        ));

        $zeroBucketCount = $bucketCount - $nonZeroBucketCount;
        $zeroBucketRatio = round($zeroBucketCount / $bucketCount, 4);

        return [
            'bucket_type' => 'weekly',
            'bucket_count' => $bucketCount,
            'non_zero_bucket_count' => $nonZeroBucketCount,
            'zero_bucket_count' => $zeroBucketCount,
            'zero_bucket_ratio' => $zeroBucketRatio,
            'is_intermittent_candidate' => (
                $bucketCount >= 12 &&
                $nonZeroBucketCount >= 4 &&
                $zeroBucketRatio >= 0.5
            ),
        ];
    }

    private function weeklyBuckets(array $context): array
    {
        $history = $context['history'] ?? [];
        $periodDays = $this->periodDays($context);

        $endDate = new \DateTimeImmutable('today');
        $startDate = $endDate->modify("-{$periodDays} days");

        $buckets = [];

        $cursor = $startDate;

        while ($cursor <= $endDate) {
            $bucket = $cursor->format('o-W');
            $buckets[$bucket] = 0.0;
            $cursor = $cursor->modify('+7 days');
        }

        foreach ($history as $movement) {
            $date = new \DateTimeImmutable((string) $movement['created_at']);

            if ($date < $startDate || $date > $endDate) {
                continue;
            }

            $bucket = $date->format('o-W');

            if (!isset($buckets[$bucket])) {
                $buckets[$bucket] = 0.0;
            }

            $buckets[$bucket] += (float) $movement['quantity'];
        }

        ksort($buckets);

        return array_values($buckets);
    }
}