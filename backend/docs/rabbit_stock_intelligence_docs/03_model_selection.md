# Rabbit — Stock Intelligence V1 Model Selection

## Current V1 Selection Strategy

Rabbit uses a conservative rule-based model-selection pipeline.

Selection order:

```text
1. criticality_only
2. threshold_only
3. simple_exponential_smoothing
4. croston_sba
5. moving_average
```

Diagnostic-only models:

```text
regression_trend
seasonal_index
```

These diagnostic models are not allowed to become the main selected reorder model in V1.

## Model Selection Logic

### 1. `criticality_only`

Used when:

```text
out_movement_count = 0
AND ved_class = V
```

Meaning:

The product has no demand history, but it is Vital. Rabbit should not ignore it just because history is missing.

### 2. `threshold_only`

Used when:

```text
out_movement_count < 3
```

Meaning:

There is no usable statistical demand history. Rabbit should rely mostly on current stock, low stock threshold, reorder point, and criticality.

### 3. `simple_exponential_smoothing`

Used when:

```text
out_movement_count >= 10
AND outlier_detected = false
```

Meaning:

Demand is frequent enough and clean enough for simple exponential smoothing.

### 4. `croston_sba`

Used when intermittent demand is confirmed:

```text
weekly_bucket_count >= 12
non_zero_bucket_count >= 4
zero_bucket_ratio >= 0.5
outlier_detected = false
```

Meaning:

Demand appears irregularly, with many zero-demand weeks. Croston/SBA is better than normal moving average for this pattern.

### 5. `moving_average`

Used when:

```text
out_movement_count >= 3
```

and stronger models are not selected.

Meaning:

Moving average is the safe fallback for usable demand history.

This is important when:

- SES is blocked by outlier detection.
- Croston is not applicable.
- Demand history exists but is not strong enough for stronger models.

## Current Healthy Behavior

```text
weak/no history        → threshold_only / criticality_only
clean frequent demand  → simple_exponential_smoothing
intermittent demand    → croston_sba
usable fallback        → moving_average
outlier blocks SES     → moving_average with outlier flags
```

## Future Refinement

Potential improvement:

```text
SES should explicitly reject intermittent profiles.
```

Reason:

A product could have 10+ OUT movements and also be intermittent. In that case, Croston/SBA may be better than SES.

For V1, current logic is acceptable because Croston, SES, moving average, and outlier fallback are already behaving defensibly.
