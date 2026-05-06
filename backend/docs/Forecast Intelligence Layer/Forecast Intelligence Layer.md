# Rabbit Backend Plan Update — Stock Intelligence Model Selection Final Decision

## Status

Before implementing the stock intelligence endpoint, we reviewed two important documents:

```text
1. Rabbit Synthetic Data Strategy
2. Forecasting course PDF: Méthode de prévision

Final decision:

Rabbit will not use one forecasting model for every product.
Rabbit will implement a Stock Intelligence Engine with model selection.

The engine chooses the calculation path based on:

available history
demand regularity
demand sparsity
criticality
seasonality
trend
outliers
stock level
supplier lead time

The goal is not to predict demand with false precision.

The goal is to answer:

Should the owner reorder this product?
How urgent is it?
How confident is the system?
Why?
1. Feature Name and Endpoint

Final feature name:

Stock Intelligence Engine

Final endpoint:

GET /stock/intelligence/reorder-recommendations

Do not use:

GET /stock/forecast/reorder-recommendations

Reason:

The feature is broader than demand forecasting.
It combines forecasting, stock risk, supplier lead time, ABC value, VED criticality, confidence, and explanation.
2. Core Architecture Decision

The engine should work in this order:

1. Load product stock and historical OUT movements.
2. Detect basic demand behavior.
3. Select the most appropriate calculation path.
4. Estimate outflow or use threshold/criticality logic.
5. Calculate reorder risk.
6. Assign recommendation, priority, confidence, reason codes, and data quality flags.

Conceptual pipeline:

Product + stock history
→ demand behavior classification
→ selected model
→ outflow estimate
→ reorder point / stock risk decision
→ recommendation output
3. Why Model Selection Is Necessary

The forecasting course separates methods into:

qualitative methods
quantitative methods
causal methods
time-series methods
trend methods
seasonality methods

This means model choice depends on whether data exists, whether it is usable, and what pattern the demand follows.

The synthetic data strategy also confirms this because it defines 12 product archetypes, each with a different demand behavior:

smooth
erratic
intermittent
sparse
lumpy
zero-demand critical
seasonal
batch
new critical
declining
outlier-driven

Therefore, Rabbit must not use one uniform method.

4. Final Model Paths
4.1 criticality_only

Use when:

Product has no or weak demand history
AND product is VED = Vital

Purpose:

Protect critical spare parts even when historical demand is zero.

Example:

VALVE-BALL-DN50
0 OUT movements
VED = V
current stock below vital minimum
recommendation = true
confidence = ESTIMATE_ONLY

Reason:

A critical spare can require stock even if it has no recent demand.
Operational risk matters more than historical movement count.

Expected output:

{
  "selected_model": "criticality_only",
  "recommendation": true,
  "confidence": "ESTIMATE_ONLY",
  "reason_codes": ["VED_VITAL_OVERRIDE"]
}
4.2 threshold_only

Use when:

Demand history is missing, too sparse, or not exploitable
AND product is not eligible for statistical forecasting

Typical conditions:

0 OUT movements
1–2 OUT movements
new product
very sparse product
missing or unreliable history

Purpose:

Avoid false precision when there is not enough data.

Logic:

recommendation = true if current_stock <= low_stock_threshold

Expected output:

{
  "selected_model": "threshold_only",
  "recommendation": true,
  "confidence": "LOW",
  "reason_codes": ["CURRENT_STOCK_AT_OR_BELOW_THRESHOLD"]
}
4.3 moving_average

Use when:

Demand is stable and regular.

Typical products:

fasteners
common consumables
regular low-cost items
stable raw materials

Synthetic example:

FAST-NUT-M8
smooth demand
daily demand
stable quantity
CV < 0.2

Purpose:

Estimate normal outflow for products with regular level demand.

Logic:

average_daily_outflow = total_out_quantity / period_days

Use this estimate inside the reorder point calculation.

4.4 simple_exponential_smoothing

Use when:

Demand is frequent but moderately variable
AND recent demand should matter more than older demand.

Typical products:

lubricants
filters
consumables with variable usage

Synthetic example:

LUBE-OIL-46
slightly erratic
400 OUT rows
variable quantities

Purpose:

React better than a plain average when recent outflow is more relevant.

Parameters:

alpha default = 0.2

Important:

Alpha is a configurable assumption.
Later it should be backtested and tuned.
4.5 croston_sba

Use when:

Demand is intermittent
AND there are enough non-zero OUT events.

Typical products:

bearings
seals
mechanical spare parts
hydraulic spare parts
low-frequency MRO parts

Minimum condition:

out_movement_count >= 10

Synthetic example:

BEAR-6205-ZZ
~28 OUT movements over 730 days
average inter-arrival around 26 days
intermittent demand

Purpose:

Handle products with many zero periods between demand events.

Important decision:

Croston/SBA is not forgotten.
It belongs in Rabbit because MRO demand is often intermittent.
But it must only activate when the product has enough demand events.

Do not use Croston/SBA for:

0 events
1–2 events
smooth daily demand
new products
criticality-only products
4.6 outlier_lumpy_adjustment

Use when:

One very large OUT movement would distort the estimate.

Synthetic examples:

PUMP-CENT-3KW
normal OUT events: 1–3 units
one outlier: 25 units

SENS-PROX-NPN
normal OUT events: 1–4 units
one outlier: 50 units

Purpose:

Avoid one project/bulk event causing excessive reorder recommendations.

Rule:

If one OUT movement > 3 × mean of other OUT movements:
    flag as outlier/lumpy
    exclude or cap it in the average
    reduce confidence

Output flags:

{
  "data_quality_flags": [
    "LUMPY_DEMAND_DETECTED",
    "OUTLIER_EXCLUDED"
  ]
}
4.7 trend_flag

Use when:

Demand is clearly increasing or declining over time.

Synthetic example:

BELT-VRIB-A60
smooth but declining demand
possible obsolescence candidate

Purpose:

Warn the owner.
Do not aggressively forecast trend in V1.

Output example:

{
  "trend_flag": "DECLINING",
  "reason_codes": ["DEMAND_DECLINING_POSSIBLE_OBSOLESCENCE"],
  "recommendation": false
}

Decision:

Use trend as an informational flag in V1.
Do not implement double exponential smoothing now.
4.8 seasonal_index

Use when:

There are at least 24 months of data
AND demand peaks repeat in the same periods.

Synthetic example:

FILT-HYD-10U
730 days of history
Q1 and Q3 peaks
maintenance-cycle seasonality

Purpose:

Detect maintenance-cycle demand peaks.

Decision:

Include seasonality as an eligible path.
For real data, activate only when enough history exists.
For synthetic demo, it can be demonstrated.

Do not implement triple exponential smoothing now.

4.9 regression / causal models

Use later only when Rabbit stores demand drivers such as:

promotions
RFQ activity
product views
supplier reliability
reviews
maintenance/project notes
confirmed future needs

Decision:

Do not implement regression in the current stage.

Reason:

Rabbit does not yet store enough explanatory variables.
5. Methods Not Implemented Now

Do not implement now:

weighted moving average
double exponential smoothing
triple exponential smoothing
regression
machine learning

Reason:

They are not useless.
They are just not the best current implementation target.

Why:

weighted moving average needs weight calibration
double smoothing is for trend forecasting, while V1 only needs trend warning
triple smoothing requires rich seasonal history
regression requires explanatory variables Rabbit does not yet store
machine learning requires much larger clean history
6. Reorder Decision Layer

Regardless of selected model, every path feeds the same decision layer.

Core fields:

current_stock
low_stock_threshold
supplier_lead_time_days
safety_stock
estimated_outflow_rate
lead_time_demand
reorder_point
recommended_reorder_quantity
estimated_reorder_value

Main formula:

lead_time_demand = estimated_daily_outflow × supplier_lead_time_days
reorder_point = lead_time_demand + safety_stock
recommendation = true if current_stock <= reorder_point

For threshold-only:

reorder_point = low_stock_threshold

For criticality-only:

reorder_point = vital_minimum_stock
7. Safety Stock Decision

Initial V1 safety stock:

Use low_stock_threshold as the main safety reference.

Practical rule:

safety_stock = max(1, low_stock_threshold * 0.5)

If threshold is missing:

data_quality_flags[] = "NO_THRESHOLD_SET"
fallback threshold = max(1, round(lead_time_demand * 1.5))

Future:

Use standard deviation-based safety stock only after enough clean periodic demand history exists.
8. VED Criticality

Add to components:

ALTER TABLE components
ADD COLUMN ved_class ENUM('V', 'E', 'D') NULL AFTER low_stock_threshold;

Meaning:

V = Vital
E = Essential
D = Desirable
NULL = not classified yet

Rules:

VED=V can force recommendation even with no demand history.
VED=E increases priority when stock is low.
VED=D does not override demand/stock logic.
NULL reduces confidence and adds a data-quality flag.

Vital minimum stock:

V: 2 units
E: 1 unit
D: 0 units
NULL: 0 units
9. Confidence Labels

Use:

HIGH
MEDIUM
LOW
ESTIMATE_ONLY

Rules:

HIGH:
- enough history for selected model
- supplier lead time known
- threshold set
- no major outlier

MEDIUM:
- usable history but some uncertainty

LOW:
- sparse history
- outlier/lumpy demand detected
- estimated lead time
- missing threshold
- new product

ESTIMATE_ONLY:
- no usable demand history
- recommendation based on threshold or criticality only

Confidence is not accuracy.

It tells the user how much trust to place in the recommendation.

10. Data Quality Flags

Return flags such as:

NO_OUT_HISTORY
LIMITED_OUT_HISTORY
SPARSE_DEMAND_HISTORY
NEW_PRODUCT
LUMPY_DEMAND_DETECTED
OUTLIER_EXCLUDED
LEAD_TIME_ESTIMATED
MISSING_LEAD_TIME
NO_THRESHOLD_SET
VED_CLASS_MISSING
MISSING_PURCHASE_COST
INSUFFICIENT_HISTORY_FOR_SEASONALITY

Purpose:

Explain why the recommendation may be uncertain.
11. Reason Codes

Return reason codes such as:

CURRENT_STOCK_ZERO
CURRENT_STOCK_AT_OR_BELOW_THRESHOLD
CURRENT_STOCK_BELOW_REORDER_POINT
VED_VITAL_OVERRIDE
CRITICAL_SPARE_MIN_STOCK
ABC_A_PRIORITY
HIGH_LEAD_TIME_RISK
PROJECTED_STOCK_BELOW_THRESHOLD
DEMAND_DECLINING_POSSIBLE_OBSOLESCENCE
OUTLIER_DEMAND_EVENT_FLAGGED

Purpose:

Explain why the system recommends or does not recommend reorder.
12. Accuracy and Backtesting

The forecasting course lists four forecast quality metrics:

MAD
MSE
MFE
MAPE

Rabbit should not implement these in the first reorder endpoint.

They belong in a future endpoint:

GET /stock/intelligence/backtest

Purpose:

Compare selected model forecasts against actual historical OUT movements.

Backtesting can later compare:

moving_average
simple_exponential_smoothing
croston_sba
seasonal_index

For the current endpoint, use:

confidence labels
data quality flags
reason codes
13. Synthetic Data Strategy

The synthetic data strategy is accepted as the demo/testing strategy.

Purpose:

Generate 730 days of OUT movement history instantly.
Demonstrate multiple demand behaviors.
Validate every code path.

Synthetic data should include 12 archetypes:

P01 smooth demand
P02 slightly erratic demand
P03 intermittent Croston/SBA hero
P04 sparse intermittent low confidence
P05 lumpy high-value outlier
P06 zero-demand vital spare
P07 seasonal maintenance-cycle demand
P08 batch demand
P09 production-batch erratic demand
P10 new critical product
P11 declining/obsolescence product
P12 intermittent product with outlier

Important rule:

Synthetic data validates that the code works.
It does not prove real-world parameter accuracy.

All synthetic stock movements should be tagged:

notes LIKE 'synthetic:%'

Rabbit schema mapping:

product_id in document → component_id in Rabbit
movement_date in document → created_at in Rabbit
note in document → notes in Rabbit

Reset rule:

DELETE FROM stock_movements
WHERE notes LIKE 'synthetic:%';

Never delete non-synthetic rows.

14. Implementation Order

Implement in this order:

1. Add ved_class to components.
2. Build base StockIntelligence endpoint.
3. Implement threshold_only.
4. Implement criticality_only.
5. Implement moving_average.
6. Implement simple_exponential_smoothing.
7. Implement Croston/SBA eligibility and calculation.
8. Implement outlier/lumpy detection.
9. Implement trend flag.
10. Implement basic seasonal index eligibility and output.
11. Add synthetic injection script later.
12. Add backtesting endpoint later.

Important:

Do not build regression, ML, double smoothing, or triple smoothing now.
15. Final Decision Summary

Rabbit will implement:

Stock Intelligence Engine with model selection.

Models/paths included in the planned engine:

criticality_only
threshold_only
moving_average
simple_exponential_smoothing
croston_sba
outlier_lumpy_adjustment
trend_flag
seasonal_index

Models explicitly postponed:

weighted moving average
double exponential smoothing
triple exponential smoothing
regression
machine learning

The system output must always include:

selected_model
model_reason
recommendation
priority
confidence
reason_codes
data_quality_flags

Main principle:

The chosen model must fit the product behavior.
Rabbit should never force one forecasting method across the whole catalogue.