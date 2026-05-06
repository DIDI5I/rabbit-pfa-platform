<?php

namespace App\Services\Chatbot;

class ClarificationPolicy
{
    public function allowedKeys(): array
    {
        return [
            'pending',
            'original_intent',
            'original_tool',
            'original_params',
            'entity_type',
            'matches',
            'created_at',
        ];
    }

    public function canStoreIntent(string $intent): bool
    {
        return in_array($intent, [
            'owner_product_details',
            'owner_product_dependencies',
            'purchase_lots_by_product',
            'stock_movements_by_component',
        ], true);
    }
}