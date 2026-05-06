<?php

namespace App\Services\Chatbot\Tools\Notification;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\NotificationService;
use Throwable;

class NotificationSummaryTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'notification_summary';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';

        try {
            $response = (new NotificationService())->list([
                'page' => 1,
                'limit' => 5,
            ]);

            $data = $response['data'] ?? [];
            $items = $data['items'] ?? [];
            $pagination = $data['pagination'] ?? [];
            $unreadCount = (int) ($data['unread_count'] ?? 0);
            $total = (int) ($pagination['total'] ?? count($items));

            return $this->result(
                'notification_summary',
                'success',
                [
                    'notifications' => $items,
                    'unread_count' => $unreadCount,
                    'total' => $total,
                    'count' => count($items),
                    'pagination' => $pagination,
                ],
                $this->meta('notification_summary', $role, 'read_only', false, count($items))
            );
        } catch (Throwable $e) {
            return $this->result(
                'notification_summary',
                'failed',
                [],
                $this->meta('notification_summary', $role, 'read_only', false, 0),
                [
                    [
                        'code' => 'notification_summary_failed',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }
}