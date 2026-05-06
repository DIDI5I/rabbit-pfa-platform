<?php

namespace App\Services\Chatbot\Intent;

class TextIntentUtils
{
    public static function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    public static function result(string $intent, array $params = [], string $confidence = 'high'): array
    {
        return [
            'intent' => $intent,
            'params' => $params,
            'confidence' => $confidence,
            'source' => 'rules',
        ];
    }

    public static function extractFirstNumber(string $text): ?int
    {
        if (preg_match('/\b(\d+)\b/', $text, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public static function extractId(string $text, array $words): ?int
    {
        foreach ($words as $word) {
            if (preg_match('/' . preg_quote($word, '/') . '\s*#?\s*(\d+)/', $text, $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

   public static function extractSearchTerm(string $text): ?string
    {
        $text = strtolower(trim($text));
        $text = trim($text, " \t\n\r\0\x0B?!.,");

        $patterns = [
            '/\bdo you have\s+(.+)/',
            '/\bdo u have\s+(.+)/',
            '/\bhave you got\s+(.+)/',
            '/\bdo you sell\s+(.+)/',
            '/\bi need\s+(.+)/',
            '/\bi want\s+(.+)/',
            '/\bi am looking for\s+(.+)/',
            '/\blooking for\s+(.+)/',
            '/\bsearch(?: for)?\s+(.+)/',
            '/\bfind\s+(.+)/',
            '/\bje cherche\s+(.+)/',
            '/\bvous avez\s+(.+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return self::normalizeSearchTerm($matches[1]);
            }
        }

        if (str_contains($text, 'pumps') || str_contains($text, 'pump')) {
            return 'pump';
        }

        if (str_contains($text, 'hydraulic')) {
            return 'hydraulic';
        }

        return null;
    }

    private static function normalizeSearchTerm(string $term): ?string
    {
        $term = strtolower(trim($term));
        $term = trim($term, " \t\n\r\0\x0B?!.,");
        
        $stopPhrases = [
            'products',
            'items',
            'things',
            'stuff',
            'anything',
            'something',
        ];

        if (in_array($term, $stopPhrases, true)) {
            return null;
        }

        $map = [
            'pump' => 'pompe',
            'pumps' => 'pompe',
            'hydraulic' => 'hydraulique',
            'hydraulics' => 'hydraulique',
            'maintenance kit' => 'kit maintenance',
            'spare parts' => 'pièces',
            'seal' => 'joint',
            'seals' => 'joint',
            'bearing' => 'roulement',
            'bearings' => 'roulement',
            'belt' => 'courroie',
            'belts' => 'courroie',
            'motor' => 'moteur',
            'motors' => 'moteur',
            'filter' => 'filtre',
            'filters' => 'filtre',
        ];

        return $map[$term] ?? $term;
    }

    public static function extractReferenceAfterKeywords(string $text, array $keywords): ?string
    {
        $text = strtolower(trim($text));
        $text = trim($text, " \t\n\r\0\x0B?!.,;:");

        foreach ($keywords as $keyword) {
            $keyword = strtolower($keyword);

            $pattern = '/\b' . preg_quote($keyword, '/') . '\s+(.+)$/';

            if (preg_match($pattern, $text, $matches)) {
                $value = trim($matches[1]);
                $value = trim($value, " \t\n\r\0\x0B?!.,;:");

                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    public static function extractSkuLikeToken(string $text): ?string
    {
        if (preg_match('/\b[A-Z]{2,}(?:-[A-Z0-9]+)+\b/i', $text, $matches)) {
            return strtoupper($matches[0]);
        }

        return null;
    }
}