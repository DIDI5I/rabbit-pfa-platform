# Stage 11C Goal

The goal of Stage 11C was to extend the safe chatbot write-action architecture from notifications and RFQs to orders.

Orders are sensitive because some status transitions affect stock.

Especially:

```text
processing
→ creates sale stock-out movements

cancelled
→ may restore stock if sale stock-out movements already exist
```

Therefore Stage 11C required clear previews for stock impact before confirmation.
