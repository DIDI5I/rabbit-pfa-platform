# Rabbit — Model Eligibility Metadata for Chatbot

## Purpose

`model_eligibility` exists mainly to support explainable intelligence and the future Rabbit chatbot.

It lets the chatbot answer questions such as:

```text
Why did Rabbit use moving average?
Why was SES not used?
Why was Croston selected?
Why is this only threshold-based?
What data is missing for stronger forecasting?
```

## Shape

```json
{
  "model_eligibility": {
    "selected": "moving_average",
    "period_days": 90,
    "eligible": [],
    "ineligible": []
  }
}
```

## Example: Moving Average with Outlier

```json
{
  "selected": "moving_average",
  "eligible": [
    {
      "model": "moving_average",
      "reason_code": "HAS_USABLE_OUT_HISTORY",
      "details": {
        "out_movement_count": 14,
        "minimum_required": 3,
        "role": "usable_history_fallback"
      }
    }
  ],
  "ineligible": [
    {
      "model": "simple_exponential_smoothing",
      "reason_code": "OUTLIER_DETECTED_REDUCES_SMOOTHING_CONFIDENCE",
      "details": {
        "out_movement_count": 14,
        "minimum_required": 10,
        "outlier_detected": true
      }
    }
  ]
}
```

## Example Chatbot Explanation

User:

```text
Why did Rabbit use moving average for CMP-BRG-6205?
```

Rabbit chatbot can answer:

```text
Rabbit used moving average because the product has usable OUT movement history, but SES was rejected because an outlier demand event was detected. Rabbit excluded the outlier from the estimate and used moving average as a safer fallback.
```

## Important Rule

The chatbot must explain using backend-provided fields only:

- `selected_model`
- `model_reason`
- `model_eligibility`
- `outlier_analysis`
- `trend_analysis`
- `seasonality_analysis`
- `reason_codes`
- `data_quality_flags`

It must not invent missing statistics or supplier/product insights.
