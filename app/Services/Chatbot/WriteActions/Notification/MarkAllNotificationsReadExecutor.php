<?php

namespace App\Services\Chatbot\WriteActions\Notification;

use App\Services\Chatbot\WriteActions\Contracts\PendingActionExecutorInterface;
use App\Services\NotificationService;

class MarkAllNotificationsReadExecutor implements PendingActionExecutorInterface
{
    public function supports(array $action): bool
    {
        return ($action['tool'] ?? null) === 'mark_all_notifications_read';
    }

    public function execute(array $identity, array $action): array
    {
        $result = (new NotificationService())->markAllAsRead();

        return [
            'executed' => true,
            'answer' => 'Confirmed. All unread notifications were marked as read.',
            'summary' => [
                'updated' => true,
                'action' => 'mark_all_notifications_read',
                'service_message' => $result['message'] ?? null,
            ],
            'items_preview' => [],
            'sources' => [
                [
                    'tool' => 'mark_all_notifications_read',
                    'status' => 'executed',
                ],
            ],
            'limitations' => [],
            'suggested_actions' => [
                'Show unread notifications',
                'Show notifications',
            ],
        ];
    }
}