<?php

namespace App\Services\Chatbot\Intent;

class CancelActionIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        $normalized = $this->normalize($text);

        $phrases = [
            'cancel',
            'cancel it',
            'no',
            'no cancel',
            'stop',
            'discard',
            'nevermind',
            'never mind',
            'annuler',
            'non',
            'stopper',
        ];

        if (in_array($normalized, $phrases, true)) {
            return TextIntentUtils::result(
                'cancel_write_action',
                [],
                'high'
            );
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