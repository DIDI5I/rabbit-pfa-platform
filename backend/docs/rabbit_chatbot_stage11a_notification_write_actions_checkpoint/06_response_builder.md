# WriteActionResponseBuilder

The response builder standardizes write-action responses.

It supports:

```text
preview
confirmed
cancelled
noPendingAction
```

Preview response shape:

```json
{
  "message": "Chatbot action requires confirmation.",
  "data": {
    "operation_type": "write_action",
    "summary": {
      "action_required": true,
      "confirmation_required": true,
      "risk_level": "low"
    },
    "result_meta": {
      "pending_action": true,
      "action_id": "..."
    },
    "limitations": [
      "This action has not been executed yet.",
      "Reply with confirm to execute, or cancel to discard."
    ]
  }
}
```
