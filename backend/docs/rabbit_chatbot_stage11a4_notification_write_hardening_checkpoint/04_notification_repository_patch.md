# NotificationRepository Patch

`markAsRead()` now returns whether a row was updated:

```php
public function markAsRead(int $notificationId, int $userId, string $role): bool
{
    $this->query(NotificationQuery::markAsRead(), [
        $notificationId,
        $userId,
        $role,
    ]);

    return $this->affectedRows() > 0;
}
```

`markAllAsRead()` now returns the number of rows updated:

```php
public function markAllAsRead(int $userId, string $role): int
{
    $this->query(NotificationQuery::markAllAsRead(), [
        $userId,
        $role,
    ]);

    return $this->affectedRows();
}
```
