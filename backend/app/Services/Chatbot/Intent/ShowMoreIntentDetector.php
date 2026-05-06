<?php

namespace App\Services\Chatbot\Intent;

class ShowMoreIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        if (TextIntentUtils::containsAny($text, [
            'show more',
            'more results',
            'next page',
            'next',
            'show me more',
            'show the rest',
            'continue',
            'voir plus',
            'suivant',
            'plus de résultats',
        ])) {
            return TextIntentUtils::result('show_more');
        }

        return null;
    }
}