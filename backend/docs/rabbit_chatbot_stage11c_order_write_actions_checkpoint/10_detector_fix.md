# Detector Fix

Initial issue:

```text
mark order 1 as delivered
```

was detected as:

```text
order_details
read_only
```

instead of:

```text
update_order_status
write_action
```

Cause:

```text
The detector looked for phrases like "mark order as delivered" but the real command contained the order ID between "order" and "as".
```

Fix:

```text
Use regex patterns that support:
mark order 1 as delivered
mark order #1 as shipped
cancel order 10
order 10 processing
```

Also required:

```text
OrderWriteIntentDetector must be registered before OrderIntentDetector.
```
