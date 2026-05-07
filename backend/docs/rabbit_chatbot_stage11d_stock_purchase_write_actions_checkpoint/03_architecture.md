# Architecture

Stage 11D follows the same safe write-action architecture used for notifications, RFQs, and orders:

```text
User message
  ↓
Intent detector
  ↓
WriteActionToolRegistry permission metadata
  ↓
PermissionGuard
  ↓
Preview tool
  ↓
PendingActionStore
  ↓
confirm / cancel / update pending action
  ↓
Executor
  ↓
Existing backend service
  ↓
Repository writes
  ↓
Audit log
  ↓
Verification by endpoint
```

The chatbot does not directly edit stock quantities. Stock changes are recorded as movements through the existing `StockService`.
