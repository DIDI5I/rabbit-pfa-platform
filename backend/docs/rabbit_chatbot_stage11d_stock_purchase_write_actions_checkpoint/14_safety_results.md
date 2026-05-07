# Safety Results

Confirmed safety behavior:

```text
No stock or purchase action executes on first message.
Preview is required.
Confirmation is required.
Invalid quantities are blocked.
Stock OUT over-removal is blocked.
No-op set-stock does not create pending action.
Purchase lot finalization blocks finalized/cancelled lots.
Purchase lot cost updates require a pending finalize action.
AI refinement stays false for write actions.
Pending actions are cleared after execution.
```
