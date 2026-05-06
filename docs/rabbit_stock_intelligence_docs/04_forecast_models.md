# Rabbit — Forecast Model Classes

## Folder Structure

```text
app/Services/StockIntelligence/ForecastModels/
    ForecastModel.php
    CriticalityOnlyModel.php
    ThresholdOnlyModel.php
    MovingAverageModel.php
    SimpleExponentialSmoothingModel.php
    CrostonSbaModel.php
    RegressionTrendModel.php
    SeasonalityAnalysisModel.php
```

## Base Class

`ForecastModel` defines the shared contract:

```php
abstract class ForecastModel
{
    abstract public function name(): string;

    abstract public function isEligible(array $context): bool;

    abstract public function estimateDailyOutflow(array $context): ?float;

    abstract public function eligibilityReason(array $context): array;

    abstract public function ineligibilityReason(array $context): array;
}
```

## Context Passed to Models

Each model receives a context array similar to:

```php
$modelContext = [
    'out_movement_count' => $outMovementCount,
    'stock_out_quantity' => $stockOutQuantity,
    'period_days' => $periodDays,
    'forecast_days' => $forecastDays,
    'ved_class' => $vedClass,
    'history' => $history,
    'outlier_analysis' => $outlierAnalysis,
];
```

## Service Responsibility

`StockIntelligenceService` should orchestrate:

- Load product rows
- Load outflow summaries
- Load movement history
- Analyze outliers
- Build model context
- Select model
- Calculate reorder values
- Apply filters
- Build summary

## Model Responsibility

Each model should handle:

- Its own eligibility
- Its own daily outflow estimate
- Its own eligibility reason
- Its own ineligibility reason

## Diagnostic Models

These models are included in eligibility metadata and direct analysis fields but are not selected as the main reorder model in V1:

```text
regression_trend
seasonal_index
```

`selectForecastModel()` should skip diagnostic-only models:

```php
if (in_array($model->name(), ['regression_trend', 'seasonal_index'], true)) {
    continue;
}
```
