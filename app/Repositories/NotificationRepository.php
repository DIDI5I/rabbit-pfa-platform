<?php

namespace App\Repositories;

use App\Queries\NotificationQuery;

class NotificationRepository extends Repository
{
    public function create(
        ?int $userId,
        ?string $role,
        string $type,
        string $title,
        string $message,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void {
        $this->query(NotificationQuery::insert(), [
            $userId,
            $role,
            $type,
            $title,
            $message,
            $referenceType,
            $referenceId,
        ]);
    }

    public function listForUser(
        int $userId,
        string $role,
        bool $unreadOnly,
        int $limit,
        int $offset
    ): array {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);

        $sql = NotificationQuery::listForUser();

        $params = [$userId, $role];

        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }

        $sql .= "
            ORDER BY created_at DESC
            LIMIT {$limit}
            OFFSET {$offset}
        ";

        $rows = $this
            ->query($sql, $params)
            ->fetchMany();

        foreach ($rows as &$row) {
            $row = $this->castNotificationRow($row);
        }

        unset($row);

        return $rows;
    }

    public function countForUser(
        int $userId,
        string $role,
        bool $unreadOnly
    ): int {
        if ($unreadOnly) {
            $row = $this
                ->query(NotificationQuery::unreadCountForUser(), [$userId, $role])
                ->fetchOne();

            return (int) ($row['unread_count'] ?? 0);
        }

        $row = $this
            ->query(NotificationQuery::countForUser(), [$userId, $role])
            ->fetchOne();

        return (int) ($row['total'] ?? 0);
    }

    public function unreadCountForUser(int $userId, string $role): int
    {
        $row = $this
            ->query(NotificationQuery::unreadCountForUser(), [$userId, $role])
            ->fetchOne();

        return (int) ($row['unread_count'] ?? 0);
    }

    public function markAsRead(int $notificationId, int $userId, string $role): bool
    {
        $this->query(NotificationQuery::markAsRead(), [
            $notificationId,
            $userId,
            $role,
        ]);

        return true;
    }

    public function markAllAsRead(int $userId, string $role): void
    {
        $this->query(NotificationQuery::markAllAsRead(), [
            $userId,
            $role,
        ]);
    }

    private function castNotificationRow(array $row): array
    {
        $row['id'] = (int) $row['id'];

        $row['user_id'] = $row['user_id'] !== null
            ? (int) $row['user_id']
            : null;

        $row['reference_id'] = $row['reference_id'] !== null
            ? (int) $row['reference_id']
            : null;

        $row['is_read'] = (bool) $row['is_read'];

        return $row;
    }
}