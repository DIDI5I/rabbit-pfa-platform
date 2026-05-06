<?php

namespace App\Services\Chatbot\Intent;

class ProductIntelligenceIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        $normalized = $this->normalize($text);

        if (!$this->mentionsProductIntelligence($normalized)) {
            return null;
        }

        $productId = $this->extractProductId($normalized);

        if ($productId !== null) {
            return TextIntentUtils::result('product_intelligence_snapshot', [
                'product_id' => $productId,
            ], 'high');
        }

        $productRef = $this->extractProductRef($normalized);

        if ($productRef !== null) {
            return TextIntentUtils::result('product_intelligence_snapshot', [
                'product_ref' => $productRef,
            ], 'high');
        }

        return TextIntentUtils::result('product_intelligence_snapshot', [], 'medium');
    }

    private function mentionsProductIntelligence(string $text): bool
    {
        return str_contains($text, 'full picture')
            || str_contains($text, 'product intelligence')
            || str_contains($text, 'product snapshot')
            || str_contains($text, 'snapshot')
            || str_contains($text, 'is product')
            || str_contains($text, 'is this product')
            || str_contains($text, 'healthy')
            || str_contains($text, 'health')
            || str_contains($text, 'what do we know about')
            || str_contains($text, 'tell me about')
            || str_contains($text, 'vue globale')
            || str_contains($text, 'sante produit')
            || str_contains($text, 'santé produit')
            || str_contains($text, 'fiche intelligence');
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
            '/(?:full picture for|product intelligence for|product snapshot for|snapshot for|tell me about|what do we know about)\s+(.+)$/i',
            '/(?:vue globale de|fiche intelligence de|santé produit de|sante produit de)\s+(.+)$/i',
            '/(?:is|is product)\s+(.+?)\s+(?:healthy|critical|important|risky)\??$/i',
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