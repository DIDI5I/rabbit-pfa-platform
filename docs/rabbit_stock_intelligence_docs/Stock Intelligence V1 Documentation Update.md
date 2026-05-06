Stock Intelligence V1 now supports a lightweight default response and an optional full diagnostic response.

This was added because the endpoint became very rich after implementing:

- model eligibility metadata
- outlier analysis
- regression trend analysis
- seasonality analysis
- model-specific explanations

These fields are useful for debugging, dashboards with explanations, and the future chatbot, but they make the normal endpoint response very large.

---

## Endpoint

```http
GET /stock/intelligence/reorder-recommendations

Default behavior now returns a compact response suitable for dashboard/frontend usage.

Optional Diagnostic Mode
GET /stock/intelligence/reorder-recommendations?include_diagnostics=true

When include_diagnostics=true, the endpoint returns the full explanation payload.

Supported Filters
GET /stock/intelligence/reorder-recommendations?only_recommended=true
GET /stock/intelligence/reorder-recommendations?priority=CRITICAL
GET /stock/intelligence/reorder-recommendations?confidence=LOW
GET /stock/intelligence/reorder-recommendations?model=moving_average
GET /stock/intelligence/reorder-recommendations?model=simple_exponential_smoothing
GET /stock/intelligence/reorder-recommendations?model=croston_sba
GET /stock/intelligence/reorder-recommendations?include_diagnostics=true

Filters can be combined:

GET /stock/intelligence/reorder-recommendations?only_recommended=true&priority=CRITICAL&include_diagnostics=true
Compact Response Behavior

By default, the endpoint removes heavy diagnostic fields from each item.

Removed by default:

model_eligibility
trend_analysis
seasonality_analysis
outlier_analysis

Kept in compact response:

product_id
name
sku
category
unit_of_measure
current_stock
low_stock_threshold
ved_class
period_days
forecast_days
out_movement_count
stock_out_quantity
average_daily_outflow
outlier_detected
excluded_outlier_quantity
preferred_supplier_id
preferred_supplier_name
supplier_lead_time_days
lead_time_estimated
lead_time_demand
safety_stock
reorder_point
recommended_reorder_quantity
latest_unit_purchase_cost
estimated_reorder_value
selected_model
model_reason
recommendation
priority
confidence
reason_codes
data_quality_flags

This makes the endpoint better for normal frontend/dashboard usage.

Diagnostic Response Behavior

When this is used:

?include_diagnostics=true

Each item includes:

model_eligibility
trend_analysis
seasonality_analysis
outlier_analysis

These fields are useful for:

debugging
backend validation
chatbot explanations
advanced owner dashboards
model transparency
Why This Matters for the Future Chatbot

The future Rabbit chatbot will need to answer questions like:

Why did Rabbit choose moving average?
Why was SES not used?
Why is this product critical?
Why is this estimate low confidence?
Is demand intermittent?
Is there a trend?
Is there enough data for seasonality?

The chatbot should call the endpoint with:

include_diagnostics=true

Then use fields like:

model_eligibility
reason_codes
data_quality_flags
trend_analysis
seasonality_analysis
outlier_analysis
model_reason

The normal dashboard does not need all of this by default.

Current Stock Intelligence V1 Status
✅ Reorder recommendation endpoint works
✅ Duplicate product/source issue fixed
✅ Filters work
✅ Forecast model classes implemented
✅ Criticality-only model implemented
✅ Threshold-only model implemented
✅ Moving average model implemented
✅ Simple exponential smoothing model implemented
✅ Croston/SBA model implemented
✅ Outlier/lumpy demand detection implemented
✅ Regression trend diagnostic implemented
✅ Seasonality diagnostic implemented
✅ Model eligibility metadata implemented
✅ Compact response mode implemented
✅ Diagnostic response mode implemented
Current Model Selection Logic
No or weak history
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
Important Design Rule

Trend and seasonality do not currently override the selected reorder model.

They are diagnostic fields only.

This prevents Rabbit from pretending to forecast from weak or insufficient data.

Implementation Summary

Controller now accepts:

'include_diagnostics' => $_GET['include_diagnostics'] ?? null

Service normalizes it with:

'include_diagnostics' => $this->parseBoolFilter($filters['include_diagnostics'] ?? null)

When diagnostics are not requested, the service strips:

unset(
    $item['model_eligibility'],
    $item['trend_analysis'],
    $item['seasonality_analysis'],
    $item['outlier_analysis']
);
Testing Completed

Tested:

GET /stock/intelligence/reorder-recommendations

Expected result:

compact response
no heavy diagnostic fields

Tested:

GET /stock/intelligence/reorder-recommendations?include_diagnostics=true

Expected result:

full diagnostic response
model_eligibility present
trend_analysis present
seasonality_analysis present
outlier_analysis present

Both passed.