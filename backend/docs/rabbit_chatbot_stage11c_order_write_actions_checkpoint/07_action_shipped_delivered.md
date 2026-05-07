# Shipped / Delivered Actions

Intent:

```text
update_order_status
```

Statuses:

```text
shipped
delivered
```

Risk level:

```text
medium
```

Impact:

```text
order status changes
client is notified
```

Tested behavior:

```text
mark order 1 as delivered
→ preview created
→ confirm executed
→ order status became delivered
```

No-op behavior:

```text
mark order 1 as delivered
→ if already delivered, no pending action is created
```
