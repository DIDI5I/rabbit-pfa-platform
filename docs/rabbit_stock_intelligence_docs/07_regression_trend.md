# Rabbit — Regression Trend Diagnostic

## Purpose

Trend analysis checks whether demand has a meaningful directional pattern over time.

It is diagnostic-only in V1.

It does not override selected model yet.

## Method

Rabbit uses linear regression over weekly demand buckets.

Returned fields:

```json
{
  "trend_analysis": {
    "enabled": true,
    "status": "LOW_R_SQUARED",
    "method": "linear_regression_weekly_buckets",
    "bucket_type": "weekly",
    "bucket_count": 13,
    "non_zero_bucket_count": 11,
    "direction": "INCREASING",
    "slope_per_week": 0.2527,
    "intercept": 2.6923,
    "r_squared": 0.1729,
    "p_value": null,
    "is_statistically_significant": null,
    "r_squared_threshold": 0.65,
    "p_value_threshold": 0.05
  }
}
```

## Quality Rule

Rabbit does not claim a meaningful trend unless the data supports it.

Current V1 gate:

```text
r_squared >= 0.65
```

p-value is not implemented yet, so:

```json
"p_value": null,
"is_statistically_significant": null
```

## Status Values

```text
INSUFFICIENT_BUCKETS
INSUFFICIENT_NON_ZERO_BUCKETS
NO_VARIATION
LOW_R_SQUARED
FLAT_TREND
ANALYZED
```

## Interpretation

Example:

```text
slope_per_week = 0.2527
r_squared = 0.1729
```

This means demand appears slightly increasing, but the trend line explains too little of the variation. Rabbit should not treat it as a strong trend.

## Future Work

Later, if p-value is implemented correctly, trend-aware forecasting can require:

```text
r_squared >= 0.65
AND p_value <= 0.05
```

Until then, trend remains diagnostic only.
