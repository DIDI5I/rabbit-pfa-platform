# Safety Results

Confirmed safety behavior:

```text
No order write executes on first message.
Order preview is required.
Confirmation is required.
PermissionGuard runs before preview.
Only owner can run order write actions.
Unsupported statuses are blocked.
Already-current statuses create no pending action.
Processing previews sale stock-out impact.
Cancelled previews possible stock restore impact.
Failed/impossible actions do not create pending actions.
AI refinement stays false for write actions.
WriteActionLogger audits preview, confirm, execute, cancel/expire events.
Pending actions are cleared after confirmation attempts.
```
