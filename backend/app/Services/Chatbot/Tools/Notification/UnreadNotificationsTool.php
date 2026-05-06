<?php

namespace App\Services\Chatbot\Tools\Notification;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\NotificationService;
use Throwable;

class UnreadNotificationsTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'unread_notifications';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';

        try {
            $service = new NotificationService();

            $countResponse = $service->unreadCount();
            $countData = $countResponse['data'] ?? [];
            $unreadCount = (int) ($countData['unread_count'] ?? 0);

            $listResponse = $service->list([
                'page' => 1,
                'limit' => 3,
                'unread_only' => true,
            ]);

            $listData = $listResponse['data'] ?? [];
            $items = $listData['items'] ?? [];

            return $this->result(
                'unread_notifications',
                'success',
                [
                    'notifications' => $items,
                    'unread_count' => $unreadCount,
                    'count' => count($items),
                    'pagination' => $listData['pagination'] ?? [],
                ],
                $this->meta('unread_notifications', $role, 'read_only', false, count($items))
            );
        } catch (Throwable $e) {
            return $this->result(
                'unread_notifications',
                'failed',
                [],
                $this->meta('unread_notifications', $role, 'read_only', false, 0),
                [
                    [
                        'code' => 'unread_notifications_failed',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }
}