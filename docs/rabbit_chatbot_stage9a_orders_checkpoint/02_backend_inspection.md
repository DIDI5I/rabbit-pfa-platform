# Backend Order Inspection

## OrderController

Read endpoints:

```text
index() -> OrderService::all()
show(id) -> OrderService::find(id)
```

Write endpoints exist but were not wired into chatbot Stage 9A:

```text
store()
updateStatus()
```

## OrderService Access Control

`OrderService::all()`:

```text
owner -> all orders
client -> only orders where order.client_id === Auth::id()
other roles -> denied
```

`OrderService::find($orderId)`:

```text
loads order
calls authorizeOrderAccess($order)
loads items if access is allowed
```

`authorizeOrderAccess()`:

```text
owner -> allowed
client -> allowed only if order.client_id === Auth::id()
else -> denied
```

This protects clients from guessing another client's order ID.
