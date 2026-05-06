<?php

namespace App\Services\Chatbot\Presenters\Notification;

use App\Services\Chatbot\Presenters\BaseResponsePresenter;

class NotificationsByTypePresenter extends BaseResponsePresenter
{
    protected const PREVIEW_LIMIT = 5;

    public function supports(string $tool): bool
    {
        return $tool === 'notifications_by_type';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $type = $toolResult['data']['type'] ?? null;
        $matchedTypes = $toolResult['data']['matched_types'] ?? [];
        $notifications = $toolResult['data']['notifications'] ?? [];
        $total = (int) ($toolResult['data']['total'] ?? count($notifications));
        $unreadCount = (int) ($toolResult['data']['unread_count'] ?? 0);

        $shown = min(self::PREVIEW_LIMIT, count($notifications));

        if ($total === 0) {
            $answer = "I found no notifications matching {$type}.";
        } else {
            $answer = "I found {$total} notification" . ($total === 1 ? '' : 's') . " matching {$type}. Showing {$shown}.";
        }

        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'type' => $type,
                'matched_types' => $matchedTypes,
                'total' => $total,
                'unread_count' => $unreadCount,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $this->previewNotifications($notifications, self::PREVIEW_LIMIT),
            [
                'has_more' => $total > $shown,
                'type' => $type,
                'matched_types' => $matchedTypes,
                'total' => $total,
                'unread_count' => $unreadCount,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $total > $shown
                ? ['Ask to see all notifications', 'Open notifications page']
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