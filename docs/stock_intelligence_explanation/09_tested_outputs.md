# Tested Outputs

## ASM-PMP-XR200

Command:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"explain stock intelligence for ASM-PMP-XR200\"}"
```

Passed with:

```text
intent = stock_intelligence_explanation
role = owner
selected_model = threshold_only
priority = HIGH
confidence = LOW
recommendation = true
current_stock = 7
reorder_point = 10
recommended_reorder_quantity = 3
```

Reason codes:

```text
CURRENT_STOCK_BELOW_THRESHOLD
CURRENT_STOCK_BELOW_REORDER_POINT
VED_VITAL_OVERRIDE
```

Data quality flags:

```text
LIMITED_OUT_HISTORY
```

## CMP-BRG-6205

Command:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"what model was used for CMP-BRG-6205\"}"
```

Passed with:

```text
selected_model = moving_average
priority = NONE
confidence = MEDIUM
recommendation = false
outlier_detected = true
excluded_outlier_quantity = 25
```

Reason codes:

```text
STOCK_ABOVE_REORDER_POINT
OUTLIER_DEMAND_EVENT_FLAGGED
```

Data quality flags:

```text
LUMPY_DEMAND_DETECTED
OUTLIER_EXCLUDED_FROM_ESTIMATE
```

## Guest Denial

Guest test passed.

Expected result:

```text
permission denied
```
