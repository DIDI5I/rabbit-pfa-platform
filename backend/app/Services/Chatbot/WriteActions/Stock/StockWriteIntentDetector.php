<?php

namespace App\Services\Chatbot\WriteActions\Stock;

use App\Services\Chatbot\Intent\IntentDetectorInterface;
use App\Services\Chatbot\Intent\TextIntentUtils;

class StockWriteIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        $normalized = $this->normalize($text);

        $componentId = $this->extractComponentId($normalized);
        $quantity = $this->extractQuantity($normalized);

        $targetStock = $this->extractTargetStock($normalized);

        if ($componentId !== null && $targetStock !== null && $this->wantsSetStock($normalized)) {
            return TextIntentUtils::result(
                'set_stock_level',
                [
                    'component_id' => $componentId,
                    'target_stock' => $targetStock,
                    'reason' => 'MANUAL_ADJUSTMENT',
                ],
                'high'
            );
        }
        if ($componentId === null || $quantity === null) {
            return null;
        }

        if ($this->wantsStockIn($normalized)) {
            return TextIntentUtils::result(
                'record_stock_movement',
                [
                    'component_id' => $componentId,
                    'type' => 'in',
                    'quantity' => $quantity,
                    'reason' => 'MANUAL_ADJUSTMENT',
                ],
                'high'
            );
        }

        if ($this->wantsStockOut($normalized)) {
            return TextIntentUtils::result(
                'record_stock_movement',
                [
                    'component_id' => $componentId,
                    'type' => 'out',
                    'quantity' => $quantity,
                    'reason' => 'MANUAL_ADJUSTMENT',
                ],
                'high'
            );
        }

        return null;
    }

    private function wantsStockIn(string $text): bool
    {
        return str_contains($text, 'stock in')
            || str_contains($text, 'record stock in')
            || str_contains($text, 'add stock')
            || str_contains($text, 'increase stock')
            || str_contains($text, 'receive stock')
            || str_contains($text, 'add inventory')
            || str_contains($text, 'increase inventory')
            || str_contains($text, 'entree stock')
            || str_contains($text, 'entrée stock')
            || str_contains($text, 'ajouter stock')
            || str_contains($text, 'augmenter stock');
    }

    private function extractComponentId(string $text): ?int
    {
        if (preg_match('/component\s*#?\s*(\d+)/i', $text, $matches) === 1) {
            return max(1, (int) $matches[1]);
        }

        if (preg_match('/product\s*#?\s*(\d+)/i', $text, $matches) === 1) {
            return max(1, (int) $matches[1]);
        }

        if (preg_match('/produit\s*#?\s*(\d+)/i', $text, $matches) === 1) {
            return max(1, (int) $matches[1]);
        }

        return null;
    }

    
    private function wantsStockOut(string $text): bool
    {
        return str_contains($text, 'stock out')
            || str_contains($text, 'record stock out')
            || str_contains($text, 'remove stock')
            || str_contains($text, 'decrease stock')
            || str_contains($text, 'deduct stock')
            || str_contains($text, 'consume stock')
            || str_contains($text, 'remove inventory')
            || str_contains($text, 'decrease inventory')
            || str_contains($text, 'sortie stock')
            || str_contains($text, 'retirer stock')
            || str_contains($text, 'diminuer stock');
    }

    private function wantsSetStock(string $text): bool
    {
        return str_contains($text, 'set stock')
            || str_contains($text, 'adjust stock')
            || str_contains($text, 'set inventory')
            || str_contains($text, 'adjust inventory')
            || str_contains($text, 'stock to')
            || str_contains($text, 'inventory to')
            || str_contains($text, 'mettre stock')
            || str_contains($text, 'ajuster stock');
    }

    private function extractTargetStock(string $text): ?float
    {
        if (preg_match('/\b(?:set|adjust)\s+stock\s+(?:of\s+)?component\s*#?\s*\d+\s+to\s+(\d+(?:\.\d+)?)/i', $text, $matches) === 1) {
            return max(0.0, (float) $matches[1]);
        }

        if (preg_match('/\bstock\s+(?:of\s+)?component\s*#?\s*\d+\s+to\s+(\d+(?:\.\d+)?)/i', $text, $matches) === 1) {
            return max(0.0, (float) $matches[1]);
        }

        if (preg_match('/\bcomponent\s*#?\s*\d+\s+stock\s+to\s+(\d+(?:\.\d+)?)/i', $text, $matches) === 1) {
            return max(0.0, (float) $matches[1]);
        }

        return null;
    }

    private function extractQuantity(string $text): ?float
    {
        if (preg_match('/quantity\s*#?\s*(\d+(?:\.\d+)?)/i', $text, $matches) === 1) {
            return max(0.0, (float) $matches[1]);
        }

        if (preg_match('/qty\s*#?\s*(\d+(?:\.\d+)?)/i', $text, $matches) === 1) {
            return max(0.0, (float) $matches[1]);
        }

        if (preg_match('/\bby\s+(\d+(?:\.\d+)?)\b/i', $text, $matches) === 1) {
            return max(0.0, (float) $matches[1]);
        }

        if (preg_match('/\badd\s+(\d+(?:\.\d+)?)\b/i', $text, $matches) === 1) {
            return max(0.0, (float) $matches[1]);
        }

        if (preg_match('/\bremove\s+(\d+(?:\.\d+)?)\b/i', $text, $matches) === 1) {
            return max(0.0, (float) $matches[1]);
        }

        if (preg_match('/\bdeduct\s+(\d+(?:\.\d+)?)\b/i', $text, $matches) === 1) {
            return max(0.0, (float) $matches[1]);
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