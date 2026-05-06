# Rabbit — Stock Intelligence V1 Refactor Update

## Purpose

Stock Intelligence V1 was refactored because `StockIntelligenceService` was becoming too large.

The service originally handled too many responsibilities:

```txt
data loading
item building
model selection
filters
summary/dashboard totals
diagnostic stripping
outlier analysis
lead time calculation
safety stock calculation
reorder point calculation
reason codes
confidence
priority

After the refactor, StockIntelligenceService is now mostly an orchestrator.

Current Architecture
app/Services/
    StockIntelligenceService.php

app/Services/StockIntelligence/
    StockIntelligenceItemBuilder.php
    StockIntelligenceSummaryBuilder.php
    StockIntelligenceFilter.php
    StockIntelligenceDiagnostics.php
    StockIntelligenceOutlierAnalyzer.php

app/Services/StockIntelligence/ForecastModels/
    ForecastModel.php
    CriticalityOnlyModel.php
    ThresholdOnlyModel.php
    MovingAverageModel.php
    SimpleExponentialSmoothingModel.php
    CrostonSbaModel.php
    RegressionTrendModel.php
    SeasonalityAnalysisModel.php
Responsibility Split
StockIntelligenceService

Main responsibility:

orchestrate the endpoint

It should:

normalize period/forecast days
load products
load outflow aggregates
load movement histories
call item builder
apply filters
strip diagnostics when needed
build summary
return API response

It should not contain model-specific logic anymore.

It should not directly instantiate forecast models.

Forecast models now belong inside StockIntelligenceItemBuilder.

StockIntelligenceItemBuilder

Main responsibility:

build one stock intelligence item for one product

It handles:

product stock context
out movement count
stock out quantity
outlier adjustment
model context
model selection
average daily outflow
lead time demand
safety stock
reorder point
recommended reorder quantity
estimated reorder value
model reason
model eligibility
trend analysis
seasonality analysis
recommendation flag
priority
confidence
reason codes
data quality flags

This is now the main business-logic builder.

StockIntelligenceSummaryBuilder

Main responsibility:

build summary/dashboard totals

It currently returns:

total_products
recommended_count
critical_count
high_count
medium_count
low_count
estimate_only_count
estimated_reorder_value
by_model
by_confidence
by_priority
data_quality_flags

This supports future dashboard cards and charts.

Example summary:

{
  "total_products": 34,
  "recommended_count": 3,
  "critical_count": 2,
  "high_count": 1,
  "estimated_reorder_value": 102850,
  "by_model": {
    "criticality_only": 2,
    "croston_sba": 1,
    "moving_average": 4,
    "simple_exponential_smoothing": 1,
    "threshold_only": 26
  }
}
StockIntelligenceFilter

Main responsibility:

normalize and apply request filters

Supported filters:

only_recommended
priority
confidence
model
include_diagnostics

Examples:

GET /stock/intelligence/reorder-recommendations?only_recommended=true
GET /stock/intelligence/reorder-recommendations?priority=CRITICAL
GET /stock/intelligence/reorder-recommendations?model=croston_sba
GET /stock/intelligence/reorder-recommendations?include_diagnostics=true
StockIntelligenceDiagnostics

Main responsibility:

strip heavy diagnostic fields from normal responses

By default, compact responses remove:

model_eligibility
trend_analysis
seasonality_analysis
outlier_analysis

When this is passed:

?include_diagnostics=true

the full diagnostic payload is returned.

This keeps dashboard responses lightweight while preserving chatbot/debug support.

StockIntelligenceOutlierAnalyzer

Main responsibility:

detect lumpy or abnormal demand events

It handles:

insufficient sample detection
median calculation
MAD-based outlier detection
max-to-median ratio guard
fallback max-to-mean ratio detection
adjusted stock out quantity
outlier reason codes

This keeps outlier logic separate from the main service and item builder.

Forecast Model Classes

Forecast models are isolated in:

app/Services/StockIntelligence/ForecastModels/

Current models:

CriticalityOnlyModel
ThresholdOnlyModel
MovingAverageModel
SimpleExponentialSmoothingModel
CrostonSbaModel
RegressionTrendModel
SeasonalityAnalysisModel
Selected Forecast Models

These can become selected_model:

criticality_only
threshold_only
simple_exponential_smoothing
croston_sba
moving_average
Diagnostic-Only Models

These do not override selected model:

regression_trend
seasonal_index

They exist for explanation and future intelligence, not direct reorder selection in V1.

Current Model Selection Logic
No/weak history
→ threshold_only / criticality_only

Clean frequent demand
→ simple_exponential_smoothing

Intermittent demand
→ croston_sba

Usable fallback history
→ moving_average

Outlier blocks SES
→ moving_average with outlier flags

Trend analysis
→ diagnostic only

Seasonality analysis
→ diagnostic only
Important Cleanup Note

After the refactor, StockIntelligenceService.php should not import or instantiate forecast models directly.

These imports should not be in StockIntelligenceService.php anymore:

use App\Services\StockIntelligence\ForecastModels\ForecastModel;
use App\Services\StockIntelligence\ForecastModels\CriticalityOnlyModel;
use App\Services\StockIntelligence\ForecastModels\ThresholdOnlyModel;
use App\Services\StockIntelligence\ForecastModels\MovingAverageModel;
use App\Services\StockIntelligence\ForecastModels\SimpleExponentialSmoothingModel;
use App\Services\StockIntelligence\ForecastModels\CrostonSbaModel;
use App\Services\StockIntelligence\ForecastModels\RegressionTrendModel;
use App\Services\StockIntelligence\ForecastModels\SeasonalityAnalysisModel;

Those belong in:

StockIntelligenceItemBuilder.php
Expected Imports in StockIntelligenceService.php
use App\Repositories\StockIntelligenceRepository;
use App\Support\ApiResponse;
use App\Services\StockIntelligence\StockIntelligenceSummaryBuilder;
use App\Services\StockIntelligence\StockIntelligenceFilter;
use App\Services\StockIntelligence\StockIntelligenceDiagnostics;
use App\Services\StockIntelligence\StockIntelligenceOutlierAnalyzer;
use App\Services\StockIntelligence\StockIntelligenceItemBuilder;
Current Compact Endpoint Test

Endpoint:

GET /stock/intelligence/reorder-recommendations

Confirmed working.

Current compact response behavior:

all 34 products returned
heavy diagnostics excluded
summary returned
model distribution returned
confidence distribution returned
priority distribution returned
data quality flag counts returned

Current tested summary:

{
  "total_products": 34,
  "recommended_count": 3,
  "critical_count": 2,
  "high_count": 1,
  "medium_count": 0,
  "low_count": 0,
  "estimate_only_count": 22,
  "estimated_reorder_value": 102850,
  "by_model": {
    "criticality_only": 2,
    "croston_sba": 1,
    "moving_average": 4,
    "simple_exponential_smoothing": 1,
    "threshold_only": 26
  },
  "by_confidence": {
    "ESTIMATE_ONLY": 22,
    "HIGH": 1,
    "LOW": 6,
    "MEDIUM": 5
  },
  "by_priority": {
    "CRITICAL": 2,
    "HIGH": 1,
    "NONE": 31
  },
  "data_quality_flags": {
    "LIMITED_OUT_HISTORY": 6,
    "LUMPY_DEMAND_DETECTED": 1,
    "NO_OUT_HISTORY": 22,
    "OUTLIER_EXCLUDED_FROM_ESTIMATE": 1
  }
}
Current Stable Status
✅ StockIntelligenceService refactored
✅ Summary moved out
✅ Filters moved out
✅ Diagnostics stripping moved out
✅ Outlier analysis moved out
✅ Item building moved out
✅ Forecast models isolated
✅ Compact response works
✅ Diagnostic response works
✅ Summary/dashboard totals work
✅ Tests passed after cleanup