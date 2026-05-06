# Tested Outputs

## Owner Test

Command:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"debug product intelligence for product 1\"}"
```

Passed.

Owner summary included:

```text
active_promotions_count = 0
review_count = 0
average_rating = null
reorder_available = true
reorder_priority = HIGH
reorder_confidence = LOW
```

Owner preview included:

```text
commercial section
same_supplier section
owner stock intelligence section
```

## Guest Test

Command:

```cmd
curl.exe -i -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"product intelligence for ASM-PMP-XR200\"}"
```

Passed.

Guest summary included:

```text
active_promotions_count = 0
review_count = 0
average_rating = null
```

Guest preview included:

```text
commercial section
```

Guest preview did not include:

```text
ids
stock_qty
low_stock_threshold
same_supplier
owner stock intelligence
reorder
cost
procurement
```
