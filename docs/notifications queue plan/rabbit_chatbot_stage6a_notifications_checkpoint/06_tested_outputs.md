# Tested Outputs

## Guest Denial

Command:

```cmd
curl.exe -i -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"show my notifications\"}"
```

Passed:

```text
role = guest
tool = notification_summary
status = denied
```

## Owner Notification Summary

Passed:

```text
You have 20 notifications, including 18 unread. Showing 5.
```

Response included:

```text
summary.total = 20
summary.unread_count = 18
items_preview = 5 notifications
result_meta.has_more = true
```

## Owner Unread Notifications

Passed:

```text
You have 18 unread notifications. Showing 3.
```

## RFQ Notifications

After detector ordering fix:

```text
show RFQ notifications
```

now maps to:

```text
notifications_by_type
```
