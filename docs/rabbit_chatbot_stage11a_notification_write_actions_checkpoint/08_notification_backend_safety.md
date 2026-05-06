# Notification Backend Safety

The chatbot uses the existing `NotificationService`.

Relevant methods:

```php
NotificationService::markAllAsRead()
NotificationService::markAsRead(int $id)
```

These use:

```php
Auth::id()
Auth::role()
```

The repository query is identity-scoped:

```sql
WHERE id = ?
AND (user_id = ? OR role = ?)
```

and:

```sql
WHERE (user_id = ? OR role = ?)
AND is_read = 0
```

So the chatbot does not pass user_id or role from user text.
