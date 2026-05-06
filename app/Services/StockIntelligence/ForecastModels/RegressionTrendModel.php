<?php

namespace App\Services\StockIntelligence\ForecastModels;

class RegressionTrendModel extends ForecastModel
{
    public function name(): string
    {
        return 'regression_trend';
    }

    public function isEligible(array $context): bool
    {
        $analysis = $this->analyze($context);

        return $analysis['enabled'] === true
            && $analysis['status'] === 'ANALYZED'
            && $analysis['r_squared'] !== null
            && $analysis['r_squared'] >= 0.65
            && $analysis['direction'] !== 'FLAT';
    }

    public function estimateDailyOutflow(array $context): ?float
    {
        /*
         * For V1, regression trend is diagnostic only.
         * Do not use it to forecast reorder quantity yet.
         */
        return null;
    }

    public function eligibilityReason(array $context): array
    {
        return [
            'model' => $this->name(),
            'reason_code' => 'STATISTICALLY_MEANINGFUL_TREND_CANDIDATE',
            'details' => $this->analyze($context),
        ];
    }

    public function ineligibilityReason(array $context): array
    {
        $analysis = $this->analyze($context);

        $reasonCode = match ($analysis['status']) {
            'INSUFFICIENT_BUCKETS' => 'REQUIRES_AT_LEAST_8_WEEKLY_BUCKETS',
            'INSUFFICIENT_NON_ZERO_BUCKETS' => 'REQUIRES_AT_LEAST_4_NON_ZERO_BUCKETS',
            'NO_VARIATION' => 'NO_DEMAND_VARIATION_TO_ANALYZE',
            'LOW_R_SQUARED' => 'TREND_NOT_STRONG_ENOUGH',
            'FLAT_TREND' => 'NO_DIRECTIONAL_TREND_DETECTED',
            default => 'TREND_ANALYSIS_NOT_ELIGIBLE',
        };

        return [
            'model' => $this->name(),
            'reason_code' => $reasonCode,
            'details' => $analysis,
        ];
    }

    public function analyze(array $context): array
    {
        $buckets = $this->weeklyBuckets($context);
        $bucketCount = count($buckets);

        if ($bucketCount < 8) {
            return $this->emptyAnalysis('INSUFFICIENT_BUCKETS', $bucketCount, $buckets);
        }

        $nonZeroCount = count(array_filter(
            $buckets,
            fn ($qty) => (float) $qty > 0
        ));

        if ($nonZeroCount < 4) {
            return $this->emptyAnalysis('INSUFFICIENT_NON_ZERO_BUCKETS', $bucketCount, $buckets);
        }

        $n = $bucketCount;

        $xValues = range(1, $n);
        $yValues = array_values(array_map('floatval', $buckets));

        $meanX = array_sum($xValues) / $n;
        $meanY = array_sum($yValues) / $n;

        $numerator = 0.0;
        $denominator = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $xDiff = $xValues[$i] - $meanX;
            $yDiff = $yValues[$i] - $meanY;

            $numerator += $xDiff * $yDiff;
            $denominator += $xDiff * $xDiff;
        }

        if ($denominator == 0.0) {
            return $this->emptyAnalysis('NO_VARIATION', $bucketCount, $buckets);
        }

        $slope = $numerator / $denominator;
        $intercept = $meanY - ($slope * $meanX);

        $ssTotal = 0.0;
        $ssResidual = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $predicted = $intercept + ($slope * $xValues[$i]);

            $ssTotal += pow($yValues[$i] - $meanY, 2);
            $ssResidual += pow($yValues[$i] - $predicted, 2);
        }

        if ($ssTotal == 0.0) {
            return $this->emptyAnalysis('NO_VARIATION', $bucketCount, $buckets);
        }

        $rSquared = 1 - ($ssResidual / $ssTotal);

        $direction = $this->directionFromSlope($slope);

        $status = 'ANALYZED';

        if ($direction === 'FLAT') {
            $status = 'FLAT_TREND';
        } elseif ($rSquared < 0.65) {
            $status = 'LOW_R_SQUARED';
        }

        return [
            'enabled' => true,
            'status' => $status,
            'method' => 'linear_regression_weekly_buckets',
            'bucket_type' => 'weekly',
            'bucket_count' => $bucketCount,
            'non_zero_bucket_count' => $nonZeroCount,
            'direction' => $direction,
            'slope_per_week' => round($slope, 4),
            'intercept' => round($intercept, 4),
            'r_squared' => round($rSquared, 4),

            /*
             * p-value is intentionally null in V1.
             * Do not claim statistical significance until p-value is implemented correctly.
             */
            'p_value' => null,
            'is_statistically_significant' => null,

            'r_squared_threshold' => 0.65,
            'p_value_threshold' => 0.05,
            'buckets' => $buckets,
        ];
    }

    private function directionFromSlope(float $slope): string
    {
        if ($slope > 0.1) {
            return 'INCREASING';
        }

        if ($slope < -0.1) {
            return 'DECLINING';
        }

        return 'FLAT';
    }

    private function emptyAnalysis(string $status, int $bucketCount, array $buckets): array
    {
        return [
            'enabled' => true,
            'status' => $status,
            'method' => 'linear_regression_weekly_buckets',
            'bucket_type' => 'weekly',
            'bucket_count' => $bucketCount,
            'non_zero_bucket_count' => count(array_filter(
                $buckets,
                fn ($qty) => (float) $qty > 0
            )),
            'direction' => null,
            'slope_per_week' => null,
            'intercept' => null,
            'r_squared' => null,
            'p_value' => null,
            'is_statistically_significant' => null,
            'r_squared_threshold' => 0.65,
            'p_value_threshold' => 0.05,
            'buckets' => $buckets,
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