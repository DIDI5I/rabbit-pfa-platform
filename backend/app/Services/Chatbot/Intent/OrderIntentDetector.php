<?php

namespace App\Services\Chatbot\Intent;

class OrderIntentDetector implements IntentDetectorInterface
{
    private const VALID_STATUSES = [
        'pending',
        'processing',
        'shipped',
        'delivered',
        'cancelled',
    ];

    private const STATUS_ALIASES = [
        'pending' => 'pending',
        'en attente' => 'pending',
        'attente' => 'pending',

        'processing' => 'processing',
        'in progress' => 'processing',
        'en cours' => 'processing',
        'traitement' => 'processing',

        'shipped' => 'shipped',
        'expediee' => 'shipped',
        'expédiée' => 'shipped',
        'expedie' => 'shipped',
        'expédié' => 'shipped',

        'delivered' => 'delivered',
        'livree' => 'delivered',
        'livrée' => 'delivered',
        'livre' => 'delivered',
        'livré' => 'delivered',

        'cancelled' => 'cancelled',
        'canceled' => 'cancelled',
        'annulee' => 'cancelled',
        'annulée' => 'cancelled',
        'annule' => 'cancelled',
        'annulé' => 'cancelled',
    ];

    public function detect(string $text): ?array
    {
        $normalized = $this->normalize($text);

        if (!$this->mentionsOrder($normalized)) {
            return null;
        }

        $orderId = $this->extractOrderId($normalized);

        if ($orderId !== null) {
            return TextIntentUtils::result('order_details', [
                'order_id' => $orderId,
            ], 'high');
        }

        $status = $this->extractStatus($normalized);

        if ($status !== null) {
            return TextIntentUtils::result('orders_by_status', [
                'status' => $status,
            ], 'high');
        }

        if ($this->mentionsRecent($normalized)) {
            return TextIntentUtils::result('recent_orders', [], 'high');
        }

        return TextIntentUtils::result('order_summary', [], 'high');
    }

    private function mentionsOrder(string $text): bool
    {
        return str_contains($text, 'order')
            || str_contains($text, 'orders')
            || str_contains($text, 'commande')
            || str_contains($text, 'commandes');
    }

    private function mentionsRecent(string $text): bool
    {
        return str_contains($text, 'recent')
            || str_contains($text, 'latest')
            || str_contains($text, 'last orders')
            || str_contains($text, 'last order')
            || str_contains($text, 'recentes')
            || str_contains($text, 'récentes')
            || str_contains($text, 'dernieres')
            || str_contains($text, 'dernières');
    }

    private function extractOrderId(string $text): ?int
    {
        if (preg_match('/\border\s*#?\s*(\d+)\b/i', $text, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/\bcommande\s*#?\s*(\d+)\b/i', $text, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function extractStatus(string $text): ?string
    {
        foreach (self::STATUS_ALIASES as $alias => $status) {
            if (str_contains($text, $alias)) {
                return $status;
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