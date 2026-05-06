<?php

namespace App\Services;

use App\Core\Auth;
use App\Repositories\NotificationRepository;
use App\Support\ApiResponse;

class NotificationService
{
    private NotificationRepository $notificationRepository;

    public function __construct()
    {
        $this->notificationRepository = new NotificationRepository();
    }

    public function notifyUser(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void {
        $this->notificationRepository->create(
            $userId,
            null,
            $type,
            $title,
            $message,
            $referenceType,
            $referenceId
        );
    }

    public function notifyRole(
        string $role,
        string $type,
        string $title,
        string $message,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void {
        $this->notificationRepository->create(
            null,
            $role,
            $type,
            $title,
            $message,
            $referenceType,
            $referenceId
        );
    }

    public function list(array $filters = []): array
    {
        $userId = Auth::id();
        $role = Auth::role();

        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = max(1, min((int) ($filters['limit'] ?? 20), 100));
        $offset = ($page - 1) * $limit;

        $unreadOnly = $this->parseBool($filters['unread_only'] ?? null) ?? false;

        $items = $this->notificationRepository->listForUser(
            $userId,
            $role,
            $unreadOnly,
            $limit,
            $offset
        );

        $total = $this->notificationRepository->countForUser(
            $userId,
            $role,
            $unreadOnly
        );

        $unreadCount = $this->notificationRepository->unreadCountForUser(
            $userId,
            $role
        );

        return ApiResponse::success(
            'Notifications fetched successfully',
            [
                'items' => $items,
                'unread_count' => $unreadCount,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => $total > 0
                        ? (int) ceil($total / $limit)
                        : 0,
                ],
            ]
        );
    }

    public function unreadCount(): array
    {
        $count = $this->notificationRepository->unreadCountForUser(
            Auth::id(),
            Auth::role()
        );

        return ApiResponse::success(
            'Unread notification count fetched successfully',
            [
                'unread_count' => $count,
            ]
        );
    }

    public function markAsRead(int $notificationId): array
    {
        $updated = $this->notificationRepository->markAsRead(
            $notificationId,
            Auth::id(),
            Auth::role()
        );

        if (!$updated) {
            return ApiResponse::error(
                'Notification not found or not accessible',
                [
                    'notification' => [
                        'Notification not found, already read, or not accessible for your account.',
                    ],
                ],
                404
            );
        }

        return ApiResponse::success(
            'Notification marked as read successfully',
            [
                'id' => $notificationId,
                'is_read' => true,
            ]
        );
    }

    public function markAllAsRead(): array
    {
        $updatedCount = $this->notificationRepository->markAllAsRead(
            Auth::id(),
            Auth::role()
        );

        return ApiResponse::success(
            'All notifications marked as read successfully',
            [
                'updated' => true,
                'updated_count' => $updatedCount,
            ]
        );
    }

    private function parseBool(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        $value = strtolower(trim((string) $value));

        if (in_array($value, ['1', 'true', 'yes'], true)) {
            return true;
        }

        if (in_array($value, ['0', 'false', 'no'], true)) {
            return false;
        }

        return null;
    }
}