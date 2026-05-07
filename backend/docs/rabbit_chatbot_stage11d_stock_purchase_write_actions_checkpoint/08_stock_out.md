# Stock OUT Action

Intent:

```text
record_stock_movement
```

Movement:

```text
type = out
reason = MANUAL_ADJUSTMENT
```

Example:

```text
record stock out for component 11 quantity 2
```

Risk level:

```text
high
```

Insufficient stock guard:

```text
requested OUT quantity cannot exceed current stock
```

Test result:

```text
component #11 current_stock 118 → 116
```

Insufficient stock test:

```text
record stock out for component 11 quantity 999999
→ blocked
→ pending_action = false
→ confirm afterward = no pending action
```
