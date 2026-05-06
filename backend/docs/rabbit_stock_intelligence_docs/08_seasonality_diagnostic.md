# Rabbit — Seasonality Diagnostic

## Purpose

Seasonality analysis checks whether demand has recurring monthly patterns.

It is diagnostic-only in V1.

It does not override selected model yet.

## Method

Rabbit uses monthly buckets and seasonal indexes.

A monthly seasonal index is:

```text
month_demand / average_monthly_demand
```

## Data Requirement

Minimum:

```text
12 monthly buckets
```

Preferred:

```text
24 monthly buckets
```

## Conservative V1 Rule

Seasonality is detected only when:

```text
peak_month_index >= 1.5
```

and enough monthly history exists.

## Common V1 Output

For current 90-day requests, seasonality should usually return:

```json
{
  "seasonality_analysis": {
    "enabled": true,
    "status": "INSUFFICIENT_MONTHLY_BUCKETS",
    "bucket_count": 5,
    "minimum_monthly_buckets_required": 12,
    "preferred_monthly_buckets": 24,
    "seasonality_detected": false
  }
}
```

This is correct. Rabbit should not fake seasonality from only a few months of data.

## Status Values

```text
INSUFFICIENT_MONTHLY_BUCKETS
INSUFFICIENT_NON_ZERO_MONTHS
NO_SEASONALITY_DETECTED
ANALYZED
```

## Chatbot Explanation

If a user asks:

```text
Does this product have seasonal demand?
```

The chatbot can answer:

```text
Rabbit does not have enough monthly history to detect seasonality. It requires at least 12 monthly buckets and currently has only 5.
```
