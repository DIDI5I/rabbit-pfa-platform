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

        $isSuccess = ($result['message'] ?? null) === 'Notification marked as read successfully';

        if (!$isSuccess) {
            return [
                'executed' => false,
                'answer' => "Notification #{$notificationId} was not found, was already read, or is not accessible.",
                'summary' => [
                    'updated' => false,
                    'action' => 'mark_notification_read',
                    'notification_id' => $notificationId,
                    'service_message' => $result['message'] ?? null,
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'mark_notification_read',
                        'status' => 'not_found_or_not_accessible',
                    ],
                ],
                'limitations' => [
                    'No notification was updated.',
                ],
                'suggested_actions' => [
                    'Show notifications',
                ],
            ];
        }

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