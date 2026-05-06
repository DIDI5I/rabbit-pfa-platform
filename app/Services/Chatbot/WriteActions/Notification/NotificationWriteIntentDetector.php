<?php

namespace App\Services\Chatbot\WriteActions\Notification;

use App\Services\Chatbot\Intent\IntentDetectorInterface;
use App\Services\Chatbot\Intent\TextIntentUtils;

class NotificationWriteIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        $normalized = $this->normalize($text);

        if ($this->wantsMarkAllRead($normalized)) {
            return TextIntentUtils::result(
                'mark_all_notifications_read',
                [],
                'high'
            );
        }

        $notificationId = $this->extractNotificationId($normalized);

        if ($notificationId !== null && $this->wantsMarkOneRead($normalized)) {
            return TextIntentUtils::result(
                'mark_notification_read',
                [
                    'notification_id' => $notificationId,
                ],
                'high'
            );
        }

        return null;
    }

    private function wantsMarkAllRead(string $text): bool
    {
        return str_contains($text, 'mark all notifications as read')
            || str_contains($text, 'mark all notification as read')
            || str_contains($text, 'mark all unread notifications as read')
            || str_contains($text, 'clear all notifications')
            || str_contains($text, 'read all notifications')
            || str_contains($text, 'tout marquer comme lu')
            || str_contains($text, 'marquer toutes les notifications comme lues');
    }

    private function wantsMarkOneRead(string $text): bool
    {
        return str_contains($text, 'mark notification')
            || str_contains($text, 'mark this notification')
            || str_contains($text, 'mark notif')
            || str_contains($text, 'notification')
            || str_contains($text, 'marquer notification');
    }

    private function extractNotificationId(string $text): ?int
    {
        if (preg_match('/(?:notification|notif)\s*#?\s*(\d+)/i', $text, $matches) === 1) {
            return max(1, (int) $matches[1]);
        }

        if (preg_match('/#\s*(\d+)/', $text, $matches) === 1) {
            return max(1, (int) $matches[1]);
        }

        return null;
    }

    private function normalize(string $text): string
    {
        $text = strtolower(trim($text));
        $text = str_replace(["'", "’", "-"], [' ', ' ', ' '], $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}