<?php

namespace App\Services\Chatbot;

class MemoryPolicy
{
    public function allowedMemoryKeys(string $role): array
    {
        return match ($role) {
            'owner' => [
                'last_tool',
                'last_intent',
                'last_params',
                'last_filters',
                'last_page',
                'next_page',
                'total_pages',
                'has_more',
            ],

            'client', 'supplier', 'guest' => [
                'last_tool',
                'last_intent',
                'last_params',
                'last_filters',
                'last_page',
                'next_page',
                'total_pages',
                'has_more',
            ],

            default => [],
        };
    }

    public function canRememberTool(string $role, string $tool): bool
    {
        $allowedTools = match ($role) {
            'owner' => [
                'catalog_search',
                'active_promotions',
                'inventory_summary',
                'inventory_alerts',
                'reorder_recommendations',
            ],

            'client', 'supplier', 'guest' => [
                'catalog_search',
                'active_promotions',
            ],

            default => [],
        };

        return in_array($tool, $allowedTools, true);
    }
}