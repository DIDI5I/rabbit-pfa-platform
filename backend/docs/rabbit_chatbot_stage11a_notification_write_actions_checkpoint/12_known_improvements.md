# Known Improvements

## 1. Zero unread notifications

Current behavior:

```text
If unread_count = 0, Rabbit still creates a pending action.
```

This is safe, but later it can be improved to return:

```text
You have no unread notifications to mark as read.
```

without creating pending action.

## 2. markAsRead affected row check

Current repository method:

```php
markAsRead(...): bool
{
    query(...);
    return true;
}
```

It returns true even if no row was updated.

Later improvement:

```text
check affected rows
return not found / not accessible when notification ID does not belong to user
```

Then Rabbit can say:

```text
Notification not found or not accessible.
```
