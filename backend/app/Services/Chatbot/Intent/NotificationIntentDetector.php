<?php

namespace App\Services\Chatbot\Intent;

class NotificationIntentDetector implements IntentDetectorInterface
{
    private const TYPE_MAP = [
        'rfq' => 'RFQ',
        'rfqs' => 'RFQ',
        'devis' => 'RFQ',

        'stock' => 'STOCK',
        'inventory' => 'STOCK',
        'inventaire' => 'STOCK',
        'low stock' => 'LOW_STOCK_ALERT',
        'stock faible' => 'LOW_STOCK_ALERT',
        'out of stock' => 'OUT_OF_STOCK_ALERT',
        'rupture' => 'OUT_OF_STOCK_ALERT',

        'purchase lot' => 'PURCHASE_LOT',
        'purchase lots' => 'PURCHASE_LOT',
        'lot achat' => 'PURCHASE_LOT',
        'lot d achat' => 'PURCHASE_LOT',
        'lots achat' => 'PURCHASE_LOT',

        'order' => 'ORDER',
        'orders' => 'ORDER',
        'commande' => 'ORDER',
        'commandes' => 'ORDER',
    ];

    public function detect(string $text): ?array
    {
        $normalized = $this->normalize($text);

        if (!$this->mentionsNotification($normalized)) {
            return null;
        }

        if ($this->mentionsUnread($normalized)) {
            return TextIntentUtils::result('unread_notifications', [], 'high');
        }

        $type = $this->extractType($normalized);

        if ($type) {
            return TextIntentUtils::result('notifications_by_type', [
                'type' => $type,
            ], 'high');
        }

        return TextIntentUtils::result('notification_summary', [], 'high');
    }

    private function mentionsNotification(string $text): bool
    {
        return str_contains($text, 'notification')
            || str_contains($text, 'notifications')
            || str_contains($text, 'alert')
            || str_contains($text, 'alerts')
            || str_contains($text, 'alerte')
            || str_contains($text, 'alertes')
            || str_contains($text, 'anything new')
            || str_contains($text, 'what happened')
            || str_contains($text, 'messages systeme')
            || str_contains($text, 'messages système');
    }

    private function mentionsUnread(string $text): bool
    {
        return str_contains($text, 'unread')
            || str_contains($text, 'non lu')
            || str_contains($text, 'non lues')
            || str_contains($text, 'not read')
            || str_contains($text, 'new notification')
            || str_contains($text, 'new notifications')
            || str_contains($text, 'nouvelles notifications');
    }

    private function extractType(string $text): ?string
    {
        foreach (self::TYPE_MAP as $word => $type) {
            if (str_contains($text, $word)) {
                return $type;
            }
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