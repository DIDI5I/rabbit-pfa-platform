# Order Tool Handlers

## OrderSummaryTool

Calls:

```php
(new OrderService())->all()
```

Returns:

```text
orders
summary
count
```

Summary includes:

```text
total
pending
processing
shipped
delivered
cancelled
total_amount
```

## RecentOrdersTool

Calls:

```php
(new OrderService())->all()
```

Sorts by `created_at` descending and returns the latest 5 orders.

## OrdersByStatusTool

Calls:

```php
(new OrderService())->all()
```

Filters by normalized status and returns up to 5 orders.

## OrderDetailsTool

Calls:

```php
(new OrderService())->find($orderId)
```

Relies on OrderService authorization to protect order-level access.
