<?php

namespace App\Services\Chatbot\WriteActions\Notification;

use App\Services\Chatbot\WriteActions\Contracts\PendingActionExecutorInterface;
use App\Services\NotificationService;

class MarkNotificationReadExecutor implements PendingActionExecutorInterface
{
    public function supports(array $action): bool
    {
        return ($action['tool'] ?? null) === 'mark_notification_read';
    }

    public function execute(array $identity, array $action): array
    {
        $notificationId = (int) ($action['params']['notification_id'] ?? 0);

        $result = (new NotificationService())->markAsRead($notificationId);

        return [
            'executed' => true,
            'answer' => "Confirmed. Notification #{$notificationId} was marked as read.",
            'summary' => [
                'updated' => true,
                'action' => 'mark_notification_read',
                'notification_id' => $notificationId,
                'service_message' => $result['message'] ?? null,
            ],
            'items_preview' => [],
            'sources' => [
                [
                    'tool' => 'mark_notification_read',
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