# Backend Source

Stage 9C uses the existing backend endpoint/service:

```php
(new StockIntelligenceService())->reorderRecommendations()
```

The endpoint returns:

```text
period_days
forecast_days
calculation_mode
filters
items
summary
```

The dashboard summary includes:

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
```

The individual item list is used to preview recommended products.
