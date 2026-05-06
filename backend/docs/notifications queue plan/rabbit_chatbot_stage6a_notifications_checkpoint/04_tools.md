# Notification Tool Handlers

## NotificationSummaryTool

Calls:

```php
(new NotificationService())->list([
    'page' => 1,
    'limit' => 5,
])
```

Returns:

```text
notifications
unread_count
total
count
pagination
```

## UnreadNotificationsTool

Calls:

```php
(new NotificationService())->unreadCount()
```

and:

```php
(new NotificationService())->list([
    'page' => 1,
    'limit' => 3,
    'unread_only' => true,
])
```

Returns latest unread notification preview.

## NotificationsByTypeTool

Calls scoped notification list:

```php
(new NotificationService())->list([
    'page' => 1,
    'limit' => 100,
])
```

Then filters already-scoped notifications by type/group.
