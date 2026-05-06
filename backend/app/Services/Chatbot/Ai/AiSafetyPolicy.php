<?php

namespace App\Services\Chatbot\Ai;

class AiSafetyPolicy
{
    private const BLOCKED_STATUSES = [
        'denied',
        'failed',
        'not_found',
        'missing_params',
        'validation_failed',
        'clarification_needed',
        'ambiguous',
        'entity_not_found',
        'entity_resolution_failed',
    ];

    private const BLOCKED_INTENTS = [
        'navigate',
        'help',
        'clarification',
        'show_more',
    ];

    public function shouldRefine(array $response): bool
    {
        $data = $response['data'] ?? [];

        $intent = $data['intent'] ?? null;
        $status = $this->sourceStatus($data);

        if (!$intent) {
            return false;
        }

        if (in_array($intent, self::BLOCKED_INTENTS, true)) {
            return false;
        }

        if ($status !== null && in_array($status, self::BLOCKED_STATUSES, true)) {
            return false;
        }

        if (!empty($data['limitations'])) {
            foreach ($data['limitations'] as $limitation) {
                $text = strtolower((string) $limitation);

                if (
                    str_contains($text, 'permission')
                    || str_contains($text, 'not available')
                    || str_contains($text, 'required')
                    || str_contains($text, 'clarify')
                    || str_contains($text, 'specify')
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    public function validateOutput(string $aiAnswer, array $safeData): array
    {
        $violations = [];

        if ($this->containsSqlLikePattern($aiAnswer)) {
            $violations[] = 'sql_like_pattern_detected';
        }

        if ($this->containsWriteActionClaim($aiAnswer)) {
            $violations[] = 'write_action_claim_detected';
        }

        $unknownNumbers = $this->unknownNumbers($aiAnswer, $safeData);
        if (!empty($unknownNumbers)) {
            $violations[] = 'unknown_numeric_values_detected';
        }

        $unknownSkus = $this->unknownSkuLikeTokens($aiAnswer, $safeData);
        if (!empty($unknownSkus)) {
            $violations[] = 'unknown_sku_like_tokens_detected';
        }

        return [
            'passed' => empty($violations),
            'violations' => $violations,
        ];
    }

    private function sourceStatus(array $data): ?string
    {
        $sources = $data['sources'] ?? [];

        if (!is_array($sources) || empty($sources)) {
            return null;
        }

        $first = $sources[0] ?? null;

        if (!is_array($first)) {
            return null;
        }

        return $first['status'] ?? null;
    }

    private function containsSqlLikePattern(string $text): bool
    {
        return preg_match('/\b(select|insert|update|delete|drop|alter|truncate)\b\s+/i', $text) === 1;
    }

    private function containsWriteActionClaim(string $text): bool
    {
        return preg_match('/\b(i\s+)?(updated|created|deleted|accepted|rejected|cancelled|canceled|placed|submitted|marked)\b/i', $text) === 1;
    }

    private function unknownNumbers(string $text, array $safeData): array
    {
        preg_match_all('/\b\d+(?:\.\d+)?\b/', $text, $matches);

        $numbersInText = array_unique($matches[0] ?? []);

        if (empty($numbersInText)) {
            return [];
        }

        $safeText = json_encode($safeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $unknown = [];

        foreach ($numbersInText as $number) {
            if (!str_contains((string) $safeText, (string) $number)) {
                $unknown[] = $number;
            }
        }

        return $unknown;
    }

    private function unknownSkuLikeTokens(string $text, array $safeData): array
    {
        preg_match_all('/\b[A-Z]{2,}(?:-[A-Z0-9]+){1,}\b/', $text, $matches);

        $tokens = array_unique($matches[0] ?? []);

        if (empty($tokens)) {
            return [];
        }

        $safeText = json_encode($safeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $unknown = [];

        foreach ($tokens as $token) {
            if (!str_contains((string) $safeText, (string) $token)) {
                $unknown[] = $token;
            }
        }

        return $unknown;
    }
}