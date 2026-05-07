# Allowed Order Statuses

`UpdateOrderStatusRequest` allows:

```text
pending
paid
processing
shipped
delivered
cancelled
```

Stage 11C chatbot support covers:

```text
processing
shipped
delivered
cancelled
```

Not all transitions are equal:

```text
shipped/delivered
→ status update + client notification

processing
→ status update + client notification + sale stock-out movements

cancelled
→ status update + client notification + possible stock restore
```
