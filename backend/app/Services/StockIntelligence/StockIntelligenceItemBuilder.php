<?php

namespace App\Services\StockIntelligence;

use App\Services\StockIntelligence\ForecastModels\ForecastModel;
use App\Services\StockIntelligence\ForecastModels\CriticalityOnlyModel;
use App\Services\StockIntelligence\ForecastModels\ThresholdOnlyModel;
use App\Services\StockIntelligence\ForecastModels\MovingAverageModel;
use App\Services\StockIntelligence\ForecastModels\SimpleExponentialSmoothingModel;
use App\Services\StockIntelligence\ForecastModels\CrostonSbaModel;
use App\Services\StockIntelligence\ForecastModels\RegressionTrendModel;
use App\Services\StockIntelligence\ForecastModels\SeasonalityAnalysisModel;

class StockIntelligenceItemBuilder
{
    /** @var ForecastModel[] */
    private array $forecastModels;

    public function __construct(
        private StockIntelligenceOutlierAnalyzer $outlierAnalyzer
    ) {
        $this->forecastModels = [
            new CriticalityOnlyModel(),
            new ThresholdOnlyModel(),
            new SimpleExponentialSmoothingModel(),
            new CrostonSbaModel(),
            new MovingAverageModel(),
            new RegressionTrendModel(),
            new SeasonalityAnalysisModel(),
        ];
    }

    public function build(
        array $product,
        ?array $outflow,
        array $history,
        int $periodDays,
        int $forecastDays
    ): array {
        $productId = (int) $product['product_id'];
        $currentStock = (float) $product['current_stock'];
        $threshold = (float) $product['low_stock_threshold'];
        $vedClass = $product['ved_class'] ?? null;

        $outMovementCount = (int) ($outflow['out_movement_count'] ?? 0);
        $stockOutQuantity = (float) ($outflow['stock_out_quantity'] ?? 0);

        $outlierAnalysis = $this->outlierAnalyzer->analyze($history);

        if ($outlierAnalysis['has_outlier']) {
            $stockOutQuantity = (float) $outlierAnalysis['adjusted_stock_out_quantity'];
        }

        $modelContext = [
            'out_movement_count' => $outMovementCount,
            'stock_out_quantity' => $stockOutQuantity,
            'period_days' => $periodDays,
            'forecast_days' => $forecastDays,
            'ved_class' => $vedClass,
            'history' => $history,
            'outlier_analysis' => $outlierAnalysis,
        ];

        $forecastModel = $this->selectForecastModel($modelContext);
        $selectedModel = $forecastModel->name();
        $averageDailyOutflow = $forecastModel->estimateDailyOutflow($modelContext);

        $leadTimeDays = $this->resolveLeadTimeDays($product);
        $leadTimeEstimated = empty($product['supplier_lead_time_days']);
        $leadTimeDemand = $averageDailyOutflow !== null
            ? round($averageDailyOutflow * $leadTimeDays, 4)
            : null;

        $safetyStock = $this->resolveSafetyStock($threshold, $vedClass);
        $reorderPoint = $this->resolveReorderPoint(
            $threshold,
            $leadTimeDemand,
            $safetyStock,
            $vedClass,
            $currentStock
        );

        $recommendedQty = max(0, (int) ceil($reorderPoint - $currentStock));

        $latestCost = isset($product['latest_unit_purchase_cost'])
            ? (float) $product['latest_unit_purchase_cost']
            : null;

        $estimatedValue = $recommendedQty > 0 && $latestCost !== null
            ? round($recommendedQty * $latestCost, 2)
            : null;

        $trendModel = new RegressionTrendModel();
        $trendAnalysis = $trendModel->analyze($modelContext);

        $seasonalityModel = new SeasonalityAnalysisModel();
        $seasonalityAnalysis = $seasonalityModel->analyze($modelContext);

        $recommendation = $recommendedQty > 0;

        $reasonCodes = $this->reasonCodes(
            $currentStock,
            $threshold,
            $reorderPoint,
            $vedClass,
            $outlierAnalysis
        );

        $dataQualityFlags = $this->dataQualityFlags(
            $outMovementCount,
            $leadTimeEstimated,
            $outlierAnalysis
        );

        $priority = $this->priority(
            $currentStock,
            $threshold,
            $vedClass,
            $recommendation
        );

        $confidence = $this->confidence(
            $outMovementCount,
            $selectedModel,
            $outlierAnalysis
        );

        return [
            'product_id' => $productId,
            'name' => $product['name'],
            'sku' => $product['sku'],
            'category' => $product['category'],
            'unit_of_measure' => $product['unit_of_measure'],
            'current_stock' => round($currentStock, 4),
            'low_stock_threshold' => round($threshold, 4),
            'ved_class' => $vedClass,

            'period_days' => $periodDays,
            'forecast_days' => $forecastDays,

            'out_movement_count' => $outMovementCount,
            'stock_out_quantity' => round($stockOutQuantity, 4),
            'average_daily_outflow' => $averageDailyOutflow,

            'outlier_detected' => (bool) $outlierAnalysis['has_outlier'],
            'excluded_outlier_quantity' => $outlierAnalysis['excluded_outlier_quantity'],
            'outlier_analysis' => $outlierAnalysis,

            'preferred_supplier_id' => isset($product['preferred_supplier_id'])
                ? (int) $product['preferred_supplier_id']
                : null,
            'preferred_supplier_name' => $product['preferred_supplier_name'] ?? null,
            'supplier_lead_time_days' => $leadTimeDays,
            'lead_time_estimated' => $leadTimeEstimated,

            'lead_time_demand' => $leadTimeDemand,
            'safety_stock' => round($safetyStock, 4),
            'reorder_point' => round($reorderPoint, 4),
            'recommended_reorder_quantity' => $recommendedQty,

            'latest_unit_purchase_cost' => $latestCost,
            'estimated_reorder_value' => $estimatedValue,

            'selected_model' => $selectedModel,
            'model_reason' => $this->modelReason($selectedModel),
            'model_eligibility' => $this->modelEligibilityFromModels(
                $forecastModel,
                $modelContext
            ),
            'trend_analysis' => $trendAnalysis,
            'seasonality_analysis' => $seasonalityAnalysis,

            'recommendation' => $recommendation,
            'priority' => $priority,
            'confidence' => $confidence,
            'reason_codes' => $reasonCodes,
            'data_quality_flags' => $dataQualityFlags,
        ];
    }

    private function selectForecastModel(array $context): ForecastModel
    {
        foreach ($this->forecastModels as $model) {
            if (in_array($model->name(), ['regression_trend', 'seasonal_index'], true)) {
                continue;
            }

            if ($model->isEligible($context)) {
                return $model;
            }
        }

        return new ThresholdOnlyModel();
    }

    private function modelEligibilityFromModels(ForecastModel $selectedModel, array $context): array
    {
        $eligible = [];
        $ineligible = [];

        foreach ($this->forecastModels as $model) {
            if ($model->isEligible($context)) {
                $eligible[] = $model->eligibilityReason($context);
            } else {
                $ineligible[] = $model->ineligibilityReason($context);
            }
        }

        return [
            'selected' => $selectedModel->name(),
            'period_days' => (int) ($context['period_days'] ?? 90),
            'eligible' => $eligible,
            'ineligible' => $ineligible,
        ];
    }

    private function resolveLeadTimeDays(array $product): int
    {
        $leadTime = (int) ($product['supplier_lead_time_days'] ?? 0);

        return $leadTime > 0 ? $leadTime : 7;
    }

    private function resolveSafetyStock(float $threshold, ?string $vedClass): float
    {
        if ($vedClass === 'V') {
            return max(5, $threshold * 0.5);
        }

        if ($vedClass === 'E') {
            return max(1, $threshold * 0.5);
        }

        return max(0, $threshold * 0.25);
    }

    private function resolveReorderPoint(
        float $threshold,
        ?float $leadTimeDemand,
        float $safetyStock,
        ?string $vedClass,
        float $currentStock
    ): float {
        if ($leadTimeDemand !== null) {
            return max($threshold, $leadTimeDemand + $safetyStock);
        }

        if ($vedClass === 'V' && $currentStock <= $threshold) {
            return max(1, $currentStock);
        }

        return $threshold;
    }

    private function reasonCodes(
        float $currentStock,
        float $threshold,
        float $reorderPoint,
        ?string $vedClass,
        array $outlierAnalysis
    ): array {
        $reasons = [];

        if ($currentStock <= 0) {
            $reasons[] = 'CURRENT_STOCK_ZERO';
        }

        if ($threshold > 0 && $currentStock <= $threshold) {
            $reasons[] = 'CURRENT_STOCK_BELOW_THRESHOLD';
        }

        if ($currentStock < $reorderPoint) {
            $reasons[] = 'CURRENT_STOCK_BELOW_REORDER_POINT';
        } elseif ($currentStock == $reorderPoint) {
            $reasons[] = 'CURRENT_STOCK_AT_REORDER_POINT';
        } else {
            $reasons[] = 'STOCK_ABOVE_REORDER_POINT';
        }

        if ($vedClass === 'V') {
            $reasons[] = 'VED_VITAL_OVERRIDE';
        }

        if ($outlierAnalysis['has_outlier']) {
            $reasons[] = 'OUTLIER_DEMAND_EVENT_FLAGGED';
        }

        return array_values(array_unique($reasons));
    }

    private function dataQualityFlags(
        int $outMovementCount,
        bool $leadTimeEstimated,
        array $outlierAnalysis
    ): array {
        $flags = [];

        if ($outMovementCount === 0) {
            $flags[] = 'NO_OUT_HISTORY';
        } elseif ($outMovementCount < 3) {
            $flags[] = 'LIMITED_OUT_HISTORY';
        }

        if ($leadTimeEstimated) {
            $flags[] = 'LEAD_TIME_ESTIMATED_DEFAULT_USED';
        }

        if ($outlierAnalysis['has_outlier']) {
            $flags[] = 'LUMPY_DEMAND_DETECTED';
            $flags[] = 'OUTLIER_EXCLUDED_FROM_ESTIMATE';
        }

        return array_values(array_unique($flags));
    }

    private function confidence(
        int $outMovementCount,
        string $selectedModel,
        array $outlierAnalysis
    ): string {
        if ($selectedModel === 'criticality_only') {
            return 'ESTIMATE_ONLY';
        }

        if ($outlierAnalysis['has_outlier']) {
            return 'MEDIUM';
        }

        if ($outMovementCount >= 10) {
            return 'HIGH';
        }

        if ($outMovementCount >= 3) {
            return 'MEDIUM';
        }

        if ($outMovementCount > 0) {
            return 'LOW';
        }

        return 'ESTIMATE_ONLY';
    }

    private function priority(
        float $currentStock,
        float $threshold,
        ?string $vedClass,
        bool $recommendation
    ): string {
        if (!$recommendation) {
            return 'NONE';
        }

        if ($currentStock <= 0) {
            return 'CRITICAL';
        }

        if ($vedClass === 'V') {
            return 'HIGH';
        }

        if ($threshold > 0 && $currentStock <= $threshold) {
            return 'HIGH';
        }

        return 'MEDIUM';
    }

    private function modelReason(string $selectedModel): string
    {
        return match ($selectedModel) {
            'criticality_only' => 'No OUT history, but product is marked as Vital.',
            'threshold_only' => 'Insufficient OUT movement history for statistical estimation.',
            'moving_average' => 'Usable OUT movement history; using average outflow over selected period.',
            'simple_exponential_smoothing' => 'Frequent OUT movement history; using exponential smoothing so recent outflow influences the estimate.',
            'croston_sba' => 'Intermittent demand profile detected; using Croston/SBA to handle many zero-demand periods.',
            default => 'Selected by stock intelligence rules.',
        };
    }
}