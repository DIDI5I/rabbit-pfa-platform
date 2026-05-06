# Backend Stock Intelligence Output

The inspected endpoint returns:

```text
period_days
forecast_days
calculation_mode
filters
items
summary
```

Each item includes fields such as:

```text
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
```

The endpoint also returns a summary containing:

```text
total_products
recommended_count
critical_count
high_count
estimated_reorder_value
by_model
by_confidence
by_priority
data_quality_flags
```
