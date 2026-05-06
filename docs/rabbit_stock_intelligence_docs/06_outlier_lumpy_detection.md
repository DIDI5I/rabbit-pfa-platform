# Rabbit — Outlier and Lumpy Demand Detection

## Purpose

Outlier detection prevents one abnormal OUT movement from distorting the demand estimate.

Example:

```text
Normal demand: 5, 6, 5, 7
One abnormal event: 25
```

Rabbit should detect the spike and use adjusted stock-out quantity for estimation.

## Current Method

Primary method:

```text
Median Absolute Deviation (MAD)
```

A value is flagged as an outlier only when both conditions pass:

```text
max_quantity > median + 3 * MAD
AND
max_quantity / median >= 2.0
```

This prevents mild normal variation from being falsely flagged.

## Why Ratio Was Added

Without the ratio rule, data like this could be falsely flagged:

```text
7, 8, 9, 12
```

Because MAD can be small.

With the ratio rule, Rabbit only flags practically meaningful spikes.

## Output

```json
{
  "outlier_detected": true,
  "excluded_outlier_quantity": 25,
  "outlier_analysis": {
    "method": "median_absolute_deviation",
    "has_outlier": true,
    "excluded_outlier_quantity": 25,
    "adjusted_stock_out_quantity": 83,
    "max_quantity": 25,
    "median_quantity": 6,
    "mad": 1,
    "outlier_threshold": 9,
    "max_to_median_ratio": 4.1667,
    "reason_code": "MAX_QUANTITY_EXCEEDS_MEDIAN_MAD_AND_RATIO_THRESHOLD"
  }
}
```

## Data Quality Flags

When outlier is detected:

```text
LUMPY_DEMAND_DETECTED
OUTLIER_EXCLUDED_FROM_ESTIMATE
```

## Reason Code

When outlier affects the recommendation:

```text
OUTLIER_DEMAND_EVENT_FLAGGED
```

## Model Selection Impact

If outlier is detected:

- SES should be rejected.
- Moving average can be used as fallback with adjusted demand.
- Confidence may be reduced.
