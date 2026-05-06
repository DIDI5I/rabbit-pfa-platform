<?php

namespace App\Services\Chatbot\WriteActions\Notification;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\Chatbot\WriteActions\PendingActionStore;
use App\Services\Chatbot\WriteActions\WriteActionResponseBuilder;

class MarkNotificationReadPreviewTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'mark_notification_read';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $notificationId = (int) ($params['notification_id'] ?? 0);

        $action = [
            'intent' => 'mark_notification_read',
            'tool' => 'mark_notification_read',
            'title' => 'Mark notification as read',
            'description' => "I can mark notification #{$notificationId} as read.",
            'params' => [
                'notification_id' => $notificationId,
            ],
            'preview' => [
                [
                    'type' => 'notification_update',
                    'notification_id' => $notificationId,
                    'action' => 'mark_as_read',
                ],
            ],
            'risk_level' => 'low',
            'ttl_seconds' => 300,
        ];

        $store = new PendingActionStore();
        $store->put($identity, $action);

        $pending = $store->get($identity);

        return (new WriteActionResponseBuilder())->preview(
            $identity,
            $pending ?? $action
        );
    }
}