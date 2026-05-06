# Invalid Notification Hardening

Before:

```text
User: mark notification 999999 as read
Rabbit: Confirm?
User: confirm
Rabbit: Confirmed. Notification #999999 was marked as read.
```

After:

```text
User: mark notification 999999 as read
Rabbit: Confirm?
User: confirm
Rabbit: Notification #999999 was not found, was already read, or is not accessible.
```

Correct response metadata:

```json
{
  "message": "Chatbot action could not be executed.",
  "result_meta": {
    "pending_action": false,
    "executed": false
  },
  "sources": [
    {
      "tool": "mark_notification_read",
      "status": "not_found_or_not_accessible"
    }
  ],
  "limitations": [
    "No notification was updated."
  ]
}
```
