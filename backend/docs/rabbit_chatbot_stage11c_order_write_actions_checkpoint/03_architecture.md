# Order Write Action Architecture

Order write action flow:

```text
User asks order write action
  ↓
OrderWriteIntentDetector extracts order_id + status
  ↓
WriteActionToolRegistry checks tool metadata and role
  ↓
PermissionGuard validates owner access
  ↓
UpdateOrderStatusPreviewTool loads order details
  ↓
Preview tool validates supported status
  ↓
Preview tool builds stock-impact preview if needed
  ↓
PendingActionStore stores action in session
  ↓
User confirms or cancels
  ↓
WriteActionExecutor routes to UpdateOrderStatusExecutor
  ↓
Executor calls OrderService::updateStatusByData()
  ↓
OrderService performs status transition and stock logic
  ↓
WriteActionLogger records lifecycle events
  ↓
Pending action cleared
```

The chatbot does not update the order repository directly.
It calls the existing `OrderService`.
