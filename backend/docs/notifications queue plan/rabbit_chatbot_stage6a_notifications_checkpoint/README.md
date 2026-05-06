# Rabbit Chatbot — Stage 6A Notifications Checkpoint

Stage 6A adds read-only notification chatbot capabilities.

Status:

```text
PASSED
```

Implemented and tested:

```text
notification_summary
unread_notifications
notifications_by_type
```

Core safety rule:

```text
Notifications are scoped to the authenticated user and role.
The chatbot must not accept user_id/client_id/supplier_id/owner_id from text.
The backend derives identity from Auth::id() and Auth::role().
```
