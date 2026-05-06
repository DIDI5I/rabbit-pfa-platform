<?php

namespace App\Services\Chatbot\Intent;

class RfqIntentDetector implements IntentDetectorInterface
{
    private const STATUSES = [
        'draft' => 'draft',
        'drafts' => 'draft',
        'brouillon' => 'draft',
        'brouillons' => 'draft',

        'open' => 'open',
        'opened' => 'open',
        'ouvert' => 'open',
        'ouverte' => 'open',
        'ouvertes' => 'open',

        'quoted' => 'quoted',
        'quote' => 'quoted',
        'devis' => 'quoted',
        'quoted rfqs' => 'quoted',

        'accepted' => 'accepted',
        'acceptée' => 'accepted',
        'acceptées' => 'accepted',

        'rejected' => 'rejected',
        'rejetée' => 'rejected',
        'rejetées' => 'rejected',

        'expired' => 'expired',
        'expirée' => 'expired',
        'expirées' => 'expired',

        'cancelled' => 'cancelled',
        'canceled' => 'cancelled',
        'annulée' => 'cancelled',
        'annulées' => 'cancelled',
    ];

    public function detect(string $text): ?array
    {
        $normalized = $this->normalize($text);

        if (!$this->mentionsRfq($normalized)) {
            return null;
        }

        $rfqId = $this->extractRfqId($normalized);

        if ($rfqId && $this->mentionsAllowedActions($normalized)) {
            return TextIntentUtils::result('rfq_allowed_actions', [
                'rfq_id' => $rfqId,
            ], 'high');
        }

        if ($rfqId && $this->mentionsDetails($normalized)) {
            return TextIntentUtils::result('rfq_details', [
                'rfq_id' => $rfqId,
            ], 'high');
        }

        if ($rfqId) {
            return TextIntentUtils::result('rfq_details', [
                'rfq_id' => $rfqId,
            ], 'high');
        }

        $status = $this->extractStatus($normalized);

        if ($status) {
            return TextIntentUtils::result('rfqs_by_status', [
                'status' => $status,
            ], 'high');
        }

        if ($this->mentionsSummary($normalized)) {
            return TextIntentUtils::result('rfq_summary', [], 'high');
        }

        return TextIntentUtils::result('rfq_summary', [], 'medium');
    }

    private function mentionsRfq(string $text): bool
    {
        return str_contains($text, 'rfq')
            || str_contains($text, 'rfqs')
            || str_contains($text, 'request for quote')
            || str_contains($text, 'requests for quote')
            || str_contains($text, 'demande de devis')
            || str_contains($text, 'demandes de devis');
    }

    private function mentionsAllowedActions(string $text): bool
    {
        return str_contains($text, 'allowed action')
            || str_contains($text, 'allowed actions')
            || str_contains($text, 'what can i do')
            || str_contains($text, 'actions allowed')
            || str_contains($text, 'available actions')
            || str_contains($text, 'actions possibles')
            || str_contains($text, 'que puis je faire')
            || str_contains($text, 'que peux je faire');
    }

    private function mentionsDetails(string $text): bool
    {
        return str_contains($text, 'detail')
            || str_contains($text, 'details')
            || str_contains($text, 'show')
            || str_contains($text, 'open')
            || str_contains($text, 'voir')
            || str_contains($text, 'afficher');
    }

    private function mentionsSummary(string $text): bool
    {
        return str_contains($text, 'summary')
            || str_contains($text, 'overview')
            || str_contains($text, 'resume')
            || str_contains($text, 'résumé')
            || str_contains($text, 'list')
            || str_contains($text, 'liste')
            || str_contains($text, 'show rfqs')
            || str_contains($text, 'all rfqs');
    }

    private function extractRfqId(string $text): ?int
    {
        if (preg_match('/\brfq\s*#?\s*(\d+)\b/i', $text, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/\bdemande\s+de\s+devis\s*#?\s*(\d+)\b/i', $text, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function extractStatus(string $text): ?string
    {
        foreach (self::STATUSES as $word => $status) {
            if (str_contains($text, $word)) {
                return $status;
            }
        }

        return null;
    }

    private function normalize(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}