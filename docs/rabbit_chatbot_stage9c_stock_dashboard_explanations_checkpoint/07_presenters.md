# Presenters

## StockIntelligenceSummaryPresenter

Example answer:

```text
Stock intelligence covers 34 products. 3 products are recommended for reorder. This includes 2 critical and 1 high-priority items. Estimated reorder value is 102850 MAD.
```

Summary includes:

```text
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
period_days
forecast_days
calculation_mode
```

Preview includes recommended products with:

```text
product_id
name
sku
category
current_stock
reorder_point
recommended_reorder_quantity
priority
confidence
selected_model
reason_codes
data_quality_flags
estimated_reorder_value
```

## StockIntelligenceDashboardExplanationPresenter

Example answer:

```text
The stock intelligence dashboard covers 34 products. 3 products are recommended for reorder. The urgent group contains 2 critical and 1 high-priority items. The most used model is threshold_only. The most common confidence label is ESTIMATE_ONLY. 22 products have ESTIMATE_ONLY confidence, usually because usable OUT movement history is missing or weak. The most common data-quality flag is NO_OUT_HISTORY.
```

It computes:

```text
dominant_model
dominant_confidence
dominant_data_quality_flag
```

from backend summary counts.
