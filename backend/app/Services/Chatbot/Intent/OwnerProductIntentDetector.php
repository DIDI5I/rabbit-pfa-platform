<?php

namespace App\Services\Chatbot\Intent;

class OwnerProductIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        $productId = TextIntentUtils::extractId($text, [
            'product',
            'produit',
            'item',
            'component',
            'composant',
        ]);

        $genericId = TextIntentUtils::extractFirstNumber($text);
        $id = $productId ?? $genericId;

        $productRef = TextIntentUtils::extractSkuLikeToken($text)
            ?? TextIntentUtils::extractReferenceAfterKeywords($text, [
                'product',
                'produit',
                'for',
                'pour',
                'of',
                'de',
            ]);

        if (TextIntentUtils::containsAny($text, [
            'internal product',
            'internal details',
            'owner product',
            'admin product',
            'backend product',
            'full product details',
            'private product details',
            'show internal product',
            'show product internal',
            'details internes',
            'produit interne',
        ])) {
            return TextIntentUtils::result('owner_product_details', [
                'product_id' => $id,
                'product_ref' => $productRef,
            ], $id || $productRef ? 'high' : 'medium');
        }

        if (TextIntentUtils::containsAny($text, [
            'dependencies',
            'dependency',
            'bom',
            'bill of materials',
            'children',
            'child parts',
            'components of product',
            'what makes up product',
            'structure of product',
            'product structure',
            'dépendances',
            'dependances',
            'nomenclature',
            'composants du produit',
        ])) {
            return TextIntentUtils::result('owner_product_dependencies', [
                'product_id' => $id,
                'product_ref' => $productRef,
            ], $id || $productRef ? 'high' : 'medium');
        }

        return null;
    }
}