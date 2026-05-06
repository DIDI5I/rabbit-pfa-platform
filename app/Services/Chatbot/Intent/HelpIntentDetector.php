<?php

namespace App\Services\Chatbot\Intent;

class HelpIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        if (TextIntentUtils::containsAny($text, [
            'help',
            'what can you do',
            'aide',
            'que peux-tu faire',
        ])) {
            return TextIntentUtils::result('role_help');
        }

        return null;
    }
}