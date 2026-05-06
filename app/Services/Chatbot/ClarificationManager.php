<?php

namespace App\Services\Chatbot;

class ClarificationManager
{
    private const SESSION_KEY = 'chatbot_pending_clarification';

    public function get(): array
    {
        return $_SESSION[self::SESSION_KEY] ?? [];
    }

    public function hasPending(): bool
    {
        $context = $this->get();

        return !empty($context['pending'])
            && !empty($context['original_intent'])
            && !empty($context['matches']);
    }

    public function remember(array $toolResult): void
    {
        $policy = new ClarificationPolicy();

        $intent = $toolResult['meta']['intent'] ?? $toolResult['tool'] ?? null;

        if (!$intent || !$policy->canStoreIntent($intent)) {
            return;
        }

        $resolution = $toolResult['data']['resolution'] ?? [];
        $matches = $resolution['matches'] ?? [];

        if (($resolution['status'] ?? null) !== 'needs_clarification') {
            return;
        }

        if (empty($matches)) {
            return;
        }

        $_SESSION[self::SESSION_KEY] = [
            'pending' => true,
            'original_intent' => $intent,
            'original_tool' => $toolResult['tool'] ?? $intent,
            'original_params' => $resolution['original_params'] ?? [],
            'entity_type' => $resolution['entity_type'] ?? null,
            'matches' => $matches,
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    public function clear(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    public function resolveSelection(string $message): array
    {
        $context = $this->get();

        if (!$this->hasPending()) {
            return [
                'status' => 'no_pending_clarification',
                'match' => null,
                'context' => [],
            ];
        }

        $message = trim($message);
        $matches = $context['matches'] ?? [];

        $selected = $this->matchById($message, $matches)
            ?? $this->matchBySku($message, $matches)
            ?? $this->matchByOrdinal($message, $matches);

        if (!$selected) {
            return [
                'status' => 'not_matched',
                'match' => null,
                'context' => $context,
            ];
        }

        return [
            'status' => 'matched',
            'match' => $selected,
            'context' => $context,
        ];
    }

    private function matchById(string $message, array $matches): ?array
    {
        if (!preg_match('/\b(?:id|product|component|produit|composant)?\s*(\d+)\b/i', $message, $m)) {
            return null;
        }

        $id = (int) $m[1];

        foreach ($matches as $match) {
            if ((int) ($match['id'] ?? 0) === $id) {
                return $match;
            }
        }

        return null;
    }

    private function matchBySku(string $message, array $matches): ?array
    {
        $message = strtoupper(trim($message));

        foreach ($matches as $match) {
            if (strtoupper($match['sku'] ?? '') === $message) {
                return $match;
            }
        }

        return null;
    }

    private function matchByOrdinal(string $message, array $matches): ?array
    {
        $message = strtolower(trim($message));

        $ordinals = [
            'first' => 1,
            '1st' => 1,
            'one' => 1,
            'premier' => 1,

            'second' => 2,
            '2nd' => 2,
            'two' => 2,
            'deuxieme' => 2,
            'deuxième' => 2,

            'third' => 3,
            '3rd' => 3,
            'three' => 3,
            'troisieme' => 3,
            'troisième' => 3,
        ];

        if (isset($ordinals[$message])) {
            $index = $ordinals[$message] - 1;
            return $matches[$index] ?? null;
        }

        if (preg_match('/^(?:number|option|choice|choix)\s+(\d+)$/i', $message, $m)) {
            $index = ((int) $m[1]) - 1;
            return $matches[$index] ?? null;
        }

        return null;
    }
}