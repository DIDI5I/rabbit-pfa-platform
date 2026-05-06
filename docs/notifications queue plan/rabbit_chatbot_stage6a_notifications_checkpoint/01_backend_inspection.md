# Backend Notification Inspection

## Controller

`NotificationController` exposes:

```text
index()          -> NotificationService::list($_GET)
unreadCount()    -> NotificationService::unreadCount()
markAsRead(id)   -> NotificationService::markAsRead(id)
markAllAsRead()  -> NotificationService::markAllAsRead()
```

Stage 6A uses only:

```text
list()
unreadCount()
```

It does not wire write actions.

## Service Scoping

`NotificationService::list()` uses:

```php
$userId = Auth::id();
$role = Auth::role();
```

and calls:

```php
listForUser($userId, $role, ...)
countForUser($userId, $role, ...)
unreadCountForUser($userId, $role)
```

## Repository Scoping

`NotificationRepository::listForUser()` receives the authenticated user ID and role and queries only scoped notifications.
