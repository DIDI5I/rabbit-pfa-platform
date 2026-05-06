# Expected Log Examples

Example successful write flow:

```json
{
  "status": "preview_created",
  "intent": "mark_notification_read",
  "tool": "mark_notification_read",
  "executed": false
}
```

```json
{
  "status": "confirmed",
  "intent": "mark_notification_read",
  "tool": "mark_notification_read",
  "confirmed": true
}
```

```json
{
  "status": "executed",
  "intent": "mark_notification_read",
  "tool": "mark_notification_read",
  "executed": true
}
```

Example cancel flow:

```json
{
  "status": "cancelled",
  "intent": "mark_all_notifications_read",
  "tool": "mark_all_notifications_read",
  "cancelled": true
}
```
