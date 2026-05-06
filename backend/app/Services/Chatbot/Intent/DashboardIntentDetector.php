<?php

namespace App\Services\Chatbot\Intent;

class DashboardIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        if (TextIntentUtils::containsAny($text, [
            'dashboard',
            'stock dashboard',
            'tableau de bord',
        ])) {
            return TextIntentUtils::result('dashboard_stock_summary');
        }

        return null;
    }
}