# Processing Action

Intent:

```text
update_order_status
```

Status:

```text
processing
```

Risk level:

```text
high
```

Impact:

```text
order status changes to processing
client is notified
sale stock-out movements are created
```

Preview includes:

```text
order_id
current_status
new_status
client_id
client_name
total_amount
stock_impact.type = sale_stock_out
affected items
side_effects
```

Tested with:

```text
order #10
```

Preview showed:

```text
component_id = 34
component = Feuille EPDM 3 mm
quantity = 30
stock_impact = sale_stock_out
```

Confirmation result:

```text
Order #10 was marked as processing and sale stock-out movements were created.
```
