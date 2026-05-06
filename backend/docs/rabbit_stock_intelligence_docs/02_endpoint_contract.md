# Rabbit — Stock Intelligence Endpoint Contract

## Endpoint

```http
GET /stock/intelligence/reorder-recommendations
```

## Query Parameters

```http
period_days=90
forecast_days=30
only_recommended=true
priority=CRITICAL
confidence=LOW
model=moving_average
```

## Supported Filters

### `period_days`

Lookback period used for OUT movement history.

Default:

```text
90
```

### `forecast_days`

Future period used for reorder planning.

Default:

```text
30
```

### `only_recommended`

Supported values:

```text
true, false, 1, 0, yes, no, on, off
```

Example:

```http
GET /stock/intelligence/reorder-recommendations?only_recommended=true
```

### `priority`

Supported values:

```text
CRITICAL
HIGH
MEDIUM
LOW
NONE
```

Example:

```http
GET /stock/intelligence/reorder-recommendations?priority=CRITICAL
```

### `confidence`

Supported values:

```text
HIGH
MEDIUM
LOW
ESTIMATE_ONLY
```

Example:

```http
GET /stock/intelligence/reorder-recommendations?confidence=LOW
```

### `model`

Supported values:

```text
criticality_only
threshold_only
moving_average
simple_exponential_smoothing
croston_sba
seasonal_index
regression_trend
```

Note:

`seasonal_index` and `regression_trend` are diagnostic models. They appear in metadata and analysis fields but should not become the main selected reorder model in V1.

## Standard Response Format

Rabbit API responses should keep the project standard:

```json
{
  "message": "Reorder recommendations fetched successfully",
  "data": {
    "period_days": 90,
    "forecast_days": 30,
    "calculation_mode": "stock_intelligence_v1",
    "filters": {},
    "items": [],
    "summary": {}
  }
}
```

## Summary Fields

```json
{
  "total_products": 34,
  "recommended_count": 4,
  "critical_count": 2,
  "high_count": 2,
  "medium_count": 0,
  "low_count": 0,
  "estimate_only_count": 1
}
```

Important:

Filtered responses should recalculate summary based on the filtered item list, not the full product list.
