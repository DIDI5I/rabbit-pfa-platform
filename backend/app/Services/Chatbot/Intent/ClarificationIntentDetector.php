<?php

namespace App\Services\Chatbot\Intent;

use App\Services\Chatbot\ClarificationManager;

class ClarificationIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        $manager = new ClarificationManager();

        if (!$manager->hasPending()) {
            return null;
        }

        $trimmed = trim($text);

        if ($this->looksLikeClarification($trimmed)) {
            return TextIntentUtils::result('clarification_response', [
                'message' => $trimmed,
            ], 'high');
        }

        return null;
    }

    private function looksLikeClarification(string $text): bool
    {
        if (preg_match('/^\d+$/', $text)) {
            return true;
        }

        if (preg_match('/^(id|product|component|produit|composant)\s+\d+$/i', $text)) {
            return true;
        }

        if (preg_match('/\b[A-Z]{2,}(?:-[A-Z0-9]+)+\b/i', $text)) {
            return true;
        }

        return in_array(strtolower($text), [
            'first',
            'second',
            'third',
            '1st',
            '2nd',
            '3rd',
            'one',
            'two',
            'three',
            'premier',
            'deuxieme',
            'deuxième',
            'troisieme',
            'troisième',
        ], true);
    }
}