# RFQ Write Action Architecture

RFQ write action flow:

```text
User asks RFQ write action
  ↓
RfqWriteIntentDetector extracts intent + rfq_id
  ↓
WriteActionToolRegistry checks tool metadata and role
  ↓
PermissionGuard validates owner access
  ↓
RFQ preview tool loads RFQ details
  ↓
Preview tool checks allowed actions from RfqService::getAllowedActions()
  ↓
Preview tool validates action-specific data requirements
  ↓
PendingActionStore stores action in session
  ↓
User confirms or cancels
  ↓
WriteActionExecutor routes to matching RFQ executor
  ↓
RFQ executor calls RfqService::*ByData()
  ↓
Backend service performs lifecycle transition
  ↓
WriteActionLogger records lifecycle events
  ↓
Pending action cleared
```

The chatbot does not directly write to the RFQ repository. It calls existing RFQ service methods.
