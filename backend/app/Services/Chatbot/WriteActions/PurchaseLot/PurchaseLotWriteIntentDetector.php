<?php

namespace App\Services\Chatbot\WriteActions\PurchaseLot;

use App\Services\Chatbot\Intent\IntentDetectorInterface;
use App\Services\Chatbot\Intent\TextIntentUtils;

class PurchaseLotWriteIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        $normalized = $this->normalize($text);

        $purchaseLotId = $this->extractPurchaseLotId($normalized);
        $costs = $this->extractCosts($normalized);

        if (!empty($costs) && $this->wantsUpdatePendingCosts($normalized)) {
            return TextIntentUtils::result(
                'update_pending_purchase_lot_costs',
                $costs,
                'high'
            );
        }
        if ($purchaseLotId !== null && $this->wantsFinalize($normalized)) {
            return TextIntentUtils::result(
                'finalize_purchase_lot',
                [
                    'purchase_lot_id' => $purchaseLotId,
                    'transport_cost' => 0,
                    'customs_cost' => 0,
                    'handling_cost' => 0,
                    'packaging_cost' => 0,
                    'order_preparation_cost' => 0,
                    'other_cost' => 0,
                ],
                'high'
            );
        }

        return null;
    }

    private function wantsFinalize(string $text): bool
    {
        return str_contains($text, 'finalize purchase lot')
            || str_contains($text, 'finalise purchase lot')
            || str_contains($text, 'receive purchase lot')
            || str_contains($text, 'finalize lot')
            || str_contains($text, 'receive lot')
            || str_contains($text, 'finaliser lot')
            || str_contains($text, 'recevoir lot');
    }

    private function wantsUpdatePendingCosts(string $text): bool
    {
        return str_contains($text, 'cost')
            || str_contains($text, 'costs')
            || str_contains($text, 'transport')
            || str_contains($text, 'customs')
            || str_contains($text, 'handling')
            || str_contains($text, 'packaging')
            || str_contains($text, 'preparation')
            || str_contains($text, 'other');
    }

    private function extractCosts(string $text): array
    {
        $costs = [];

        $patterns = [
            'transport_cost' => [
                '/transport\s+cost\s+(\d+(?:\.\d+)?)/i',
                '/transport\s+(\d+(?:\.\d+)?)/i',
            ],
            'customs_cost' => [
                '/customs\s+cost\s+(\d+(?:\.\d+)?)/i',
                '/customs\s+(\d+(?:\.\d+)?)/i',
                '/douane\s+(\d+(?:\.\d+)?)/i',
            ],
            'handling_cost' => [
                '/handling\s+cost\s+(\d+(?:\.\d+)?)/i',
                '/handling\s+(\d+(?:\.\d+)?)/i',
            ],
            'packaging_cost' => [
                '/packaging\s+cost\s+(\d+(?:\.\d+)?)/i',
                '/packaging\s+(\d+(?:\.\d+)?)/i',
            ],
            'order_preparation_cost' => [
                '/order\s+preparation\s+cost\s+(\d+(?:\.\d+)?)/i',
                '/preparation\s+cost\s+(\d+(?:\.\d+)?)/i',
                '/preparation\s+(\d+(?:\.\d+)?)/i',
            ],
            'other_cost' => [
                '/other\s+cost\s+(\d+(?:\.\d+)?)/i',
                '/other\s+(\d+(?:\.\d+)?)/i',
            ],
        ];

        foreach ($patterns as $field => $fieldPatterns) {
            foreach ($fieldPatterns as $pattern) {
                if (preg_match($pattern, $text, $matches) === 1) {
                    $costs[$field] = max(0.0, (float) $matches[1]);
                    break;
                }
            }
        }

        return $costs;
    }

    private function extractPurchaseLotId(string $text): ?int
    {
        if (preg_match('/purchase\s+lot\s*#?\s*(\d+)/i', $text, $matches) === 1) {
            return max(1, (int) $matches[1]);
        }

        if (preg_match('/lot\s*#?\s*(\d+)/i', $text, $matches) === 1) {
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