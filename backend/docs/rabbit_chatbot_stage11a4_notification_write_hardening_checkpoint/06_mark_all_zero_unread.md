# Mark-All Zero Unread Hardening

Before:

```text
User: mark all notifications as read
Rabbit: You have no unread notifications. Confirm?
```

After:

```text
User: mark all notifications as read
Rabbit: You have no unread notifications to mark as read.
```

Response now has:

```json
{
  "summary": {
    "action_required": false,
    "confirmation_required": false,
    "unread_count": 0
  },
  "result_meta": {
    "pending_action": false,
    "executed": false
  }
}
```

No pending action is created.
