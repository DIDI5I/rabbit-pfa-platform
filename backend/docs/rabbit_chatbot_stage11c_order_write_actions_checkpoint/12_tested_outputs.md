# Tested Outputs Summary

## Normal HTTP endpoint after service refactor

Test:

```text
PATCH /orders/1/status
{"status":"shipped"}
```

Result:

```text
Order status updated successfully
status = shipped
```

## Delivered action

Test:

```text
mark order 1 as delivered
confirm
GET /orders/1
```

Result:

```text
Order #1 was marked as delivered.
status = delivered
```

## Processing action

Test:

```text
mark order 10 as processing
confirm
GET /orders/10
```

Result:

```text
Order #10 was marked as processing.
stock impact = sale_stock_out_movements_created
status = processing
```

## Cancel action

Test:

```text
cancel order 10
confirm
GET /orders/10
cancel order 10 again
```

Result:

```text
Order #10 was cancelled.
stock impact = stock_restore_logic_applied
status = cancelled
second cancel = no action needed
```
