<?php

namespace App\Services\Chatbot\WriteActions\Notification;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\Chatbot\WriteActions\PendingActionStore;
use App\Services\Chatbot\WriteActions\WriteActionResponseBuilder;
use App\Services\NotificationService;

class MarkAllNotificationsReadPreviewTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'mark_all_notifications_read';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $unreadResponse = (new NotificationService())->unreadCount();
        $unreadCount = (int) ($unreadResponse['data']['unread_count'] ?? 0);

        $action = [
            'intent' => 'mark_all_notifications_read',
            'tool' => 'mark_all_notifications_read',
            'title' => 'Mark all notifications as read',
            'description' => $unreadCount > 0
                ? "I can mark {$unreadCount} unread notification" . ($unreadCount === 1 ? '' : 's') . " as read."
                : 'You currently have no unread notifications to mark as read.',
            'params' => [],
            'preview' => [
                [
                    'type' => 'notification_bulk_update',
                    'unread_count' => $unreadCount,
                    'action' => 'mark_all_as_read',
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