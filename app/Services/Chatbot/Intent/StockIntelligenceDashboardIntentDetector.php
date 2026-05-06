<?php

namespace App\Services\Chatbot\Intent;

class StockIntelligenceDashboardIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        $normalized = $this->normalize($text);

        if (!$this->mentionsStockDashboard($normalized)) {
            return null;
        }

        if ($this->mentionsExplanation($normalized)) {
            return TextIntentUtils::result(
                'stock_intelligence_dashboard_explanation',
                [],
                'high'
            );
        }

        return TextIntentUtils::result(
            'stock_intelligence_summary',
            [],
            'high'
        );
    }

    private function mentionsStockDashboard(string $text): bool
    {
        return str_contains($text, 'stock intelligence summary')
            || str_contains($text, 'stock intelligence dashboard')
            || str_contains($text, 'reorder dashboard')
            || str_contains($text, 'reorder summary')
            || str_contains($text, 'global stock intelligence')
            || str_contains($text, 'stock dashboard')
            || str_contains($text, 'reorder recommendations summary')
            || str_contains($text, 'all reorder recommendations')
            || str_contains($text, 'why are there')
            || str_contains($text, 'why is most confidence')
            || str_contains($text, 'what models are being used')
            || str_contains($text, 'model distribution')
            || str_contains($text, 'confidence distribution')
            || str_contains($text, 'data quality flags')
            || str_contains($text, 'résumé intelligence stock')
            || str_contains($text, 'resume intelligence stock')
            || str_contains($text, 'tableau de bord stock')
            || str_contains($text, 'résumé réapprovisionnement')
            || str_contains($text, 'resume reapprovisionnement');
    }

    private function mentionsExplanation(string $text): bool
    {
        return str_contains($text, 'why')
            || str_contains($text, 'explain')
            || str_contains($text, 'explanation')
            || str_contains($text, 'what models')
            || str_contains($text, 'model distribution')
            || str_contains($text, 'confidence distribution')
            || str_contains($text, 'data quality')
            || str_contains($text, 'pourquoi')
            || str_contains($text, 'expliquer');
    }

    private function normalize(string $text): string
    {
        $text = strtolower(trim($text));
        $text = str_replace(["'", "’", "-"], [' ', ' ', ' '], $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}