# Write-Action Architecture

The write-action flow is:

```text
User asks for write action
  ↓
IntentClassifier detects write intent
  ↓
ToolRegistry identifies it as write_action
  ↓
PermissionGuard checks role
  ↓
Preview tool creates pending action
  ↓
PendingActionStore stores action in session
  ↓
Chatbot asks for confirmation
  ↓
User replies confirm or cancel
  ↓
ConfirmActionIntentDetector / CancelActionIntentDetector handles response
  ↓
WriteActionExecutor executes matching executor only after confirmation
  ↓
Pending action is cleared
```

This prevents accidental writes.
