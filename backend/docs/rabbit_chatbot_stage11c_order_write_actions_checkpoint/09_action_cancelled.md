# Cancelled Action

Intent:

```text
update_order_status
```

Status:

```text
cancelled
```

Risk level:

```text
high
```

Impact:

```text
order status changes to cancelled
client is notified
stock restore logic is applied
```

Preview includes:

```text
stock_impact.type = possible_stock_restore
affected items
note explaining stock restore only occurs if sale stock-out movement exists and restore has not already happened
```

Tested with:

```text
order #10
```

Confirmation result:

```text
Order #10 was cancelled and stock restore logic was applied.
```

No-op behavior after cancellation:

```text
cancel order 10
→ Order #10 is already cancelled.
→ pending_action = false
```
