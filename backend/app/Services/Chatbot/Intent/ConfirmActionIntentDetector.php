<?php

namespace App\Services\Chatbot\Intent;

class ConfirmActionIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        $normalized = $this->normalize($text);

        $phrases = [
            'confirm',
            'confirmed',
            'yes confirm',
            'yes do it',
            'do it',
            'execute',
            'proceed',
            'go ahead',
            'valider',
            'confirmer',
            'oui',
            'vas y',
        ];

        if (in_array($normalized, $phrases, true)) {
            return TextIntentUtils::result(
                'confirm_write_action',
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