# NotificationService Patch

`markAsRead()` now returns an error when nothing was updated:

```text
Notification not found or not accessible
```

This can happen when:

```text
notification ID does not exist
notification does not belong to the current user/role
notification was already read
```

`markAllAsRead()` now includes:

```text
updated_count
```

in the response data.
