# Order Presenters

## OrderSummaryPresenter

Example answer:

```text
I found 11 orders. Showing 5.
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
shown
```

## RecentOrdersPresenter

Example answer:

```text
Showing 5 recent orders.
```

## OrdersByStatusPresenter

Example answer:

```text
I found 3 orders with status pending. Showing 3.
```

## OrderDetailsPresenter

Example answer:

```text
Order #6 is currently pending. It has 1 item and a total amount of 100 MAD. Client: Ahmed Acheteur.
```

Owner previews can include:

```text
client_id
client_name
component_id
supplier_id
```

Client previews should be limited to their own scoped orders and avoid exposing unnecessary client identity fields.
