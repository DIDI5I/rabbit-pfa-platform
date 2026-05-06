<?php

namespace App\Services\Chatbot\Intent;

class StockIntelligenceExplanationIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        $normalized = $this->normalize($text);

        if (!$this->mentionsStockExplanation($normalized)) {
            return null;
        }

        $productId = $this->extractProductId($normalized);

        if ($productId !== null) {
            return TextIntentUtils::result('stock_intelligence_explanation', [
                'product_id' => $productId,
            ], 'high');
        }

        $productRef = $this->extractProductRef($normalized);

        if ($productRef !== null) {
            return TextIntentUtils::result('stock_intelligence_explanation', [
                'product_ref' => $productRef,
            ], 'high');
        }

        return TextIntentUtils::result('stock_intelligence_explanation', [], 'medium');
    }

    private function mentionsStockExplanation(string $text): bool
    {
        return (
            str_contains($text, 'why should i reorder')
            || str_contains($text, 'why reorder')
            || str_contains($text, 'explain reorder')
            || str_contains($text, 'explain stock intelligence')
            || str_contains($text, 'stock intelligence for')
            || str_contains($text, 'why is confidence')
            || str_contains($text, 'why confidence')
            || str_contains($text, 'what model was used')
            || str_contains($text, 'selected model')
            || str_contains($text, 'why is this high priority')
            || str_contains($text, 'why is this critical')
            || str_contains($text, 'explain recommendation')
            || str_contains($text, 'explain the recommendation')
            || str_contains($text, 'explain restock')
            || str_contains($text, 'pourquoi recommander')
            || str_contains($text, 'expliquer le réapprovisionnement')
            || str_contains($text, 'expliquer le reapprovisionnement')
        );
    }

    private function extractProductId(string $text): ?int
    {
        if (preg_match('/\bproduct\s*#?\s*(\d+)\b/i', $text, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/\bproduit\s*#?\s*(\d+)\b/i', $text, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function extractProductRef(string $text): ?string
    {
        $patterns = [
            '/(?:why should i reorder|why reorder|explain reorder|explain stock intelligence for|stock intelligence for|explain recommendation for|explain the recommendation for|what model was used for)\s+(.+)$/i',
            '/(?:why is confidence .* for|why confidence .* for|why is this high priority for|why is this critical for)\s+(.+)$/i',
            '/(?:pourquoi recommander|expliquer le réapprovisionnement de|expliquer le reapprovisionnement de)\s+(.+)$/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $ref = trim($m[1]);
                $ref = trim($ref, " ?.,;:");

                if ($ref !== '') {
                    return $ref;
                }
            }
        }

        $sku = $this->extractSkuLikeToken($text);

        if ($sku !== null) {
            return $sku;
        }

        return null;
    }

    private function extractSkuLikeToken(string $text): ?string
    {
        if (preg_match('/\b[A-Z]{2,}(?:-[A-Z0-9]+){1,}\b/i', $text, $m)) {
            return strtoupper($m[0]);
        }

        return null;
    }

    private function normalize(string $text): string
    {
        $text = strtolower(trim($text));
        $text = str_replace(["'", "’"], [' ', ' '], $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}