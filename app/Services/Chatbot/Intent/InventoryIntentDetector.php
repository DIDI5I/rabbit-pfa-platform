<?php

namespace App\Services\Chatbot\Intent;

class InventoryIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        if (TextIntentUtils::containsAny($text, [
            'low stock',
            'out of stock',
            'stock alert',
            'inventory alert',
            'which items are low',
            'items are low',
            'what is low',
            'what is running low',
            'running low',
            'low items',
            'low inventory',
            'low availability',
            'rupture',
            'alerte stock',
            'stock faible',
        ])) {
            return TextIntentUtils::result('inventory_alerts');
        }

        if (TextIntentUtils::containsAny($text, [
            'inventory',
            'stock overview',
            'stock summary',
            'inventaire',
        ])) {
            return TextIntentUtils::result('inventory_summary');
        }

        return null;
    }
}