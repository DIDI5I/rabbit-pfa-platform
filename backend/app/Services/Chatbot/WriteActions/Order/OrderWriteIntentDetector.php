<?php

namespace App\Services\Chatbot\WriteActions\Order;

use App\Services\Chatbot\Intent\IntentDetectorInterface;
use App\Services\Chatbot\Intent\TextIntentUtils;

class OrderWriteIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        $normalized = $this->normalize($text);

        $orderId = $this->extractOrderId($normalized);

        if ($orderId === null) {
            return null;
        }

        $status = $this->extractStatus($normalized);

        if ($status === null) {
            return null;
        }

        return TextIntentUtils::result(
            'update_order_status',
            [
                'order_id' => $orderId,
                'status' => $status,
            ],
            'high'
        );
    }

    private function extractOrderId(string $text): ?int
    {
        if (preg_match('/order\s*#?\s*(\d+)/i', $text, $matches) === 1) {
            return max(1, (int) $matches[1]);
        }

        if (preg_match('/commande\s*#?\s*(\d+)/i', $text, $matches) === 1) {
            return max(1, (int) $matches[1]);
        }

        return null;
    }

    private function extractStatus(string $text): ?string
    {
        if (
            preg_match('/\b(mark\s+)?order\s*#?\s*\d+\s+as\s+processing\b/i', $text) === 1
            || preg_match('/\bprocess\s+order\s*#?\s*\d+\b/i', $text) === 1
            || preg_match('/\border\s*#?\s*\d+\s+processing\b/i', $text) === 1
            || preg_match('/\btraiter\s+commande\s*#?\s*\d+\b/i', $text) === 1
            || preg_match('/\bcommande\s*#?\s*\d+\s+(comme\s+)?en\s+traitement\b/i', $text) === 1
        ) {
            return 'processing';
        }

        if (
            preg_match('/\bcancel\s+order\s*#?\s*\d+\b/i', $text) === 1
            || preg_match('/\b(mark\s+)?order\s*#?\s*\d+\s+as\s+cancelled\b/i', $text) === 1
            || preg_match('/\b(mark\s+)?order\s*#?\s*\d+\s+as\s+canceled\b/i', $text) === 1
            || preg_match('/\border\s*#?\s*\d+\s+cancelled\b/i', $text) === 1
            || preg_match('/\border\s*#?\s*\d+\s+canceled\b/i', $text) === 1
            || preg_match('/\bannuler\s+commande\s*#?\s*\d+\b/i', $text) === 1
            || preg_match('/\bcommande\s*#?\s*\d+\s+(comme\s+)?annul[ée]e?\b/i', $text) === 1
        ) {
            return 'cancelled';
        }
        if (
            preg_match('/\b(mark\s+)?order\s*#?\s*\d+\s+as\s+shipped\b/i', $text) === 1
            || preg_match('/\bship\s+order\s*#?\s*\d+\b/i', $text) === 1
            || preg_match('/\border\s*#?\s*\d+\s+shipped\b/i', $text) === 1
            || preg_match('/\bcommande\s*#?\s*\d+\s+(comme\s+)?exp[eé]di[eé]e?\b/i', $text) === 1
        ) {
            return 'shipped';
        }

        if (
            preg_match('/\b(mark\s+)?order\s*#?\s*\d+\s+as\s+delivered\b/i', $text) === 1
            || preg_match('/\bdeliver\s+order\s*#?\s*\d+\b/i', $text) === 1
            || preg_match('/\border\s*#?\s*\d+\s+delivered\b/i', $text) === 1
            || preg_match('/\bcommande\s*#?\s*\d+\s+(comme\s+)?livr[eé]e?\b/i', $text) === 1
        ) {
            return 'delivered';
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