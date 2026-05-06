# Rabbit Chatbot — Stage 9A Orders Checkpoint

Status: PASSED

Stage 9A adds read-only order chatbot tools.

Implemented tools:

```text
order_summary
recent_orders
orders_by_status
order_details
```

Core safety rule:

```text
Orders are role-scoped by OrderService.
The chatbot must not accept client_id/user_id from text.
Owner can see all orders.
Client can see only their own orders.
Guest and supplier are denied.
```
