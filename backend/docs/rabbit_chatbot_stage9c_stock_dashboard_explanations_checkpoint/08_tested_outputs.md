# Tested Outputs

## Stock Intelligence Summary

Command:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"debug stock intelligence summary\"}"
```

Passed with:

```text
total_products = 34
recommended_count = 3
critical_count = 2
high_count = 1
estimated_reorder_value = 102850
calculation_mode = stock_intelligence_v1
```

Recommended preview included:

```text
CMP-HYD-GP11
CMP-HYD-FLT10
ASM-PMP-XR200
```

## Dashboard Explanation

Command:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"debug explain stock intelligence dashboard\"}"
```

Passed with:

```text
dominant_model = threshold_only
dominant_confidence = ESTIMATE_ONLY
dominant_data_quality_flag = NO_OUT_HISTORY
```

The answer explained why many products are ESTIMATE_ONLY: missing or weak OUT movement history.

Critical preview included:

```text
CMP-HYD-GP11
CMP-HYD-FLT10
```

High-priority preview included:

```text
ASM-PMP-XR200
```

## Guest Denial

Guest access should be denied for:

```text
show stock intelligence summary
explain stock intelligence dashboard
```

Expected result:

```text
You do not have permission to access this information.
```
