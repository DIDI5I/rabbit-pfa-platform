<?php

namespace App\Services\Chatbot\Intent;

class CostIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        if (!TextIntentUtils::containsAny($text, [
            'cost rollup',
            'rollup',
            'material cost',
            'cost us',
            'cost internally',
            'internal cost',
            'how much does',
            'how much is',
            'what does it cost',
            'what does product',
            'coût',
            'cout',
            'combien coûte',
            'combien coute',
        ])) {
            return null;
        }

        $productId = TextIntentUtils::extractId($text, [
            'product',
            'produit',
            'item',
            'component',
            'composant',
        ]);

        $genericId = TextIntentUtils::extractFirstNumber($text);

        return TextIntentUtils::result('cost_rollup', [
            'product_id' => $productId ?? $genericId,
        ], $productId || $genericId ? 'high' : 'medium');
    }
}