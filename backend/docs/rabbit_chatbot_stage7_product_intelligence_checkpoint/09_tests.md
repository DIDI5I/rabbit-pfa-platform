# Tested Outputs

## Owner

Command:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"debug product intelligence for product 1\"}"
```

Passed with:

```text
intent = product_intelligence_snapshot
role = owner
tool = product_intelligence_snapshot
status = used
explicit_relationships = 9
compatible_alternatives = 1
spare_parts = 5
same_category = 3
reorder_available = true
reorder_priority = HIGH
reorder_confidence = LOW
reorder_reason_codes = CURRENT_STOCK_BELOW_THRESHOLD, CURRENT_STOCK_BELOW_REORDER_POINT, VED_VITAL_OVERRIDE
```

## Guest

Command:

```cmd
curl.exe -i -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"product intelligence for ASM-PMP-XR200\"}"
```

Passed with public-safe output.

Guest output included product identity, availability, compatible alternatives, spare parts, same category products, and relationship counts.

Guest output did not include IDs, stock quantities, thresholds, same-supplier data, owner stock intelligence, reorder, cost, or procurement.
