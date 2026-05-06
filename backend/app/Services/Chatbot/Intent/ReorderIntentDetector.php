<?php

namespace App\Services\Chatbot\Intent;

class ReorderIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        if (TextIntentUtils::containsAny($text, [
            'reorder',
            'restock',
            'restocking',
            'needs restocking',
            'what needs restocking',
            'what should i reorder',
            'recommendation',
            'réapprovisionnement',
            'recommander',
        ])) {
            return TextIntentUtils::result('reorder_recommendations');
        }

        return null;
    }
}