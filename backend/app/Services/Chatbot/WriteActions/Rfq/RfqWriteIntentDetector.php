<?php

namespace App\Services\Chatbot\WriteActions\Rfq;

use App\Services\Chatbot\Intent\IntentDetectorInterface;
use App\Services\Chatbot\Intent\TextIntentUtils;

class RfqWriteIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        $normalized = $this->normalize($text);

        $rfqId = $this->extractRfqId($normalized);
        if ($rfqId !== null && $this->wantsAccept($normalized)) {
            return TextIntentUtils::result(
                'accept_rfq',
                [
                    'rfq_id' => $rfqId,
                ],
                'high'
            );
        }
        if ($rfqId !== null && $this->wantsReject($normalized)) {
            return TextIntentUtils::result(
                'reject_rfq',
                [
                    'rfq_id' => $rfqId,
                ],
                'high'
            );
        }

        return null;
    }

    private function wantsReject(string $text): bool
    {
        return str_contains($text, 'reject rfq')
            || str_contains($text, 'reject request for quote')
            || str_contains($text, 'refuse rfq')
            || str_contains($text, 'decline rfq')
            || str_contains($text, 'rejeter rfq')
            || str_contains($text, 'refuser rfq')
            || str_contains($text, 'rejeter demande de devis')
            || str_contains($text, 'refuser demande de devis');
    }

    private function extractRfqId(string $text): ?int
    {
        if (preg_match('/rfq\s*#?\s*(\d+)/i', $text, $matches) === 1) {
            return max(1, (int) $matches[1]);
        }

        if (preg_match('/demande de devis\s*#?\s*(\d+)/i', $text, $matches) === 1) {
            return max(1, (int) $matches[1]);
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

    private function wantsAccept(string $text): bool
    {
        return str_contains($text, 'accept rfq')
            || str_contains($text, 'approve rfq')
            || str_contains($text, 'validate rfq')
            || str_contains($text, 'accept request for quote')
            || str_contains($text, 'accepter rfq')
            || str_contains($text, 'valider rfq')
            || str_contains($text, 'approuver rfq')
            || str_contains($text, 'accepter demande de devis')
            || str_contains($text, 'valider demande de devis');
    }
}