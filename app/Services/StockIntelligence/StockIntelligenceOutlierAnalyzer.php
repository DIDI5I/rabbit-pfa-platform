<?php

namespace App\Services\StockIntelligence;

class StockIntelligenceOutlierAnalyzer
{
    public function analyze(array $history): array
    {
        $quantities = array_values(array_map(
            fn ($movement) => (float) $movement['quantity'],
            $history
        ));

        $sum = array_sum($quantities);

        if (count($quantities) < 4) {
            return [
                'method' => 'insufficient_sample',
                'has_outlier' => false,
                'excluded_outlier_quantity' => null,
                'adjusted_stock_out_quantity' => $sum,
                'max_quantity' => !empty($quantities) ? max($quantities) : null,
                'sample_size' => count($quantities),
                'reason_code' => 'REQUIRES_AT_LEAST_4_OUT_MOVEMENTS',
            ];
        }

        sort($quantities);

        $median = $this->median($quantities);

        $deviations = array_map(
            fn ($qty) => abs($qty - $median),
            $quantities
        );

        sort($deviations);

        $mad = $this->median($deviations);
        $max = max($quantities);

        if ($mad > 0) {
            $threshold = $median + (3 * $mad);
            $ratioToMedian = $median > 0 ? $max / $median : null;

            if ($max > $threshold && $ratioToMedian !== null && $ratioToMedian >= 2.0) {
                return [
                    'method' => 'median_absolute_deviation',
                    'has_outlier' => true,
                    'excluded_outlier_quantity' => $max,
                    'adjusted_stock_out_quantity' => $sum - $max,
                    'max_quantity' => $max,
                    'median_quantity' => $median,
                    'mad' => $mad,
                    'outlier_threshold' => round($threshold, 4),
                    'max_to_median_ratio' => round($ratioToMedian, 4),
                    'sample_size' => count($quantities),
                    'reason_code' => 'MAX_QUANTITY_EXCEEDS_MEDIAN_MAD_AND_RATIO_THRESHOLD',
                ];
            }

            return [
                'method' => 'median_absolute_deviation',
                'has_outlier' => false,
                'excluded_outlier_quantity' => null,
                'adjusted_stock_out_quantity' => $sum,
                'max_quantity' => $max,
                'median_quantity' => $median,
                'mad' => $mad,
                'outlier_threshold' => round($threshold, 4),
                'max_to_median_ratio' => $ratioToMedian !== null ? round($ratioToMedian, 4) : null,
                'sample_size' => count($quantities),
                'reason_code' => 'NO_OUTLIER_DETECTED',
            ];
        }

        $others = $quantities;
        array_pop($others);

        if (empty($others)) {
            return [
                'method' => 'fallback_ratio',
                'has_outlier' => false,
                'excluded_outlier_quantity' => null,
                'adjusted_stock_out_quantity' => $sum,
                'max_quantity' => $max,
                'sample_size' => count($quantities),
                'reason_code' => 'NOT_ENOUGH_COMPARISON_VALUES',
            ];
        }

        $meanOthers = array_sum($others) / count($others);
        $ratio = $meanOthers > 0 ? $max / $meanOthers : null;

        if ($ratio !== null && $ratio > 3) {
            return [
                'method' => 'fallback_max_to_mean_ratio',
                'has_outlier' => true,
                'excluded_outlier_quantity' => $max,
                'adjusted_stock_out_quantity' => $sum - $max,
                'max_quantity' => $max,
                'mean_without_max' => round($meanOthers, 4),
                'max_to_mean_ratio' => round($ratio, 4),
                'sample_size' => count($quantities),
                'reason_code' => 'MAX_QUANTITY_EXCEEDS_3X_MEAN_OF_OTHERS',
            ];
        }

        return [
            'method' => 'fallback_max_to_mean_ratio',
            'has_outlier' => false,
            'excluded_outlier_quantity' => null,
            'adjusted_stock_out_quantity' => $sum,
            'max_quantity' => $max,
            'mean_without_max' => round($meanOthers, 4),
            'max_to_mean_ratio' => $ratio !== null ? round($ratio, 4) : null,
            'sample_size' => count($quantities),
            'reason_code' => 'NO_OUTLIER_DETECTED',
        ];
    }

    private function median(array $values): float
    {
        $count = count($values);

        if ($count === 0) {
            return 0.0;
        }

        sort($values);

        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (float) $values[$middle];
        }

        return ((float) $values[$middle - 1] + (float) $values[$middle]) / 2;
    }
}