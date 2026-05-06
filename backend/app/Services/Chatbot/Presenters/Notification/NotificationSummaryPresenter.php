<?php

namespace App\Services\Chatbot\Presenters\Notification;

use App\Services\Chatbot\Presenters\BaseResponsePresenter;

class NotificationSummaryPresenter extends BaseResponsePresenter
{
    protected const PREVIEW_LIMIT = 5;

    public function supports(string $tool): bool
    {
        return $tool === 'notification_summary';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $notifications = $toolResult['data']['notifications'] ?? [];
        $unreadCount = (int) ($toolResult['data']['unread_count'] ?? 0);
        $total = (int) ($toolResult['data']['total'] ?? count($notifications));
        $pagination = $toolResult['data']['pagination'] ?? [];

        $shown = min(self::PREVIEW_LIMIT, count($notifications));

        if ($total === 0) {
            $answer = 'You have no notifications available for your role.';
        } elseif ($unreadCount > 0) {
            $answer = "You have {$total} notification" . ($total === 1 ? '' : 's') . ", including {$unreadCount} unread. Showing {$shown}.";
        } else {
            $answer = "You have {$total} notification" . ($total === 1 ? '' : 's') . ". Showing {$shown}.";
        }

        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'total' => $total,
                'unread_count' => $unreadCount,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $this->previewNotifications($notifications, self::PREVIEW_LIMIT),
            [
                'has_more' => $total > $shown,
                'total' => $total,
                'unread_count' => $unreadCount,
                'shown' => $shown,
                'shown_this_response' => $shown,
                'page' => $pagination['page'] ?? 1,
                'limit' => $pagination['limit'] ?? self::PREVIEW_LIMIT,
                'total_pages' => $pagination['total_pages'] ?? null,
            ],
            $unreadCount > 0
                ? ['Show unread notifications', 'Open notifications page']
                : ['Open notifications page']
        );
    }

    private function previewNotifications(array $notifications, int $limit): array
    {
        return array_map(function (array $notification) {
            return [
                'id' => $notification['id'] ?? null,
                'type' => $notification['type'] ?? null,
                'title' => $notification['title'] ?? null,
                'message' => $notification['message'] ?? null,
                'is_read' => $notification['is_read'] ?? null,
                'reference_type' => $notification['reference_type'] ?? null,
                'reference_id' => $notification['reference_id'] ?? null,
                'created_at' => $notification['created_at'] ?? null,
            ];
        }, array_slice($notifications, 0, $limit));
    }
}