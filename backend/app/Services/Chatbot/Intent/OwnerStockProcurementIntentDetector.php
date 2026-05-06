<?php

namespace App\Services\Chatbot\Intent;

class OwnerStockProcurementIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        $productId = TextIntentUtils::extractId($text, [
            'product',
            'produit',
            'item',
            'article',
        ]);

        $componentId = TextIntentUtils::extractId($text, [
            'component',
            'composant',
            'part',
            'pièce',
            'piece',
        ]);

        $genericId = TextIntentUtils::extractFirstNumber($text);

        $productRef = TextIntentUtils::extractSkuLikeToken($text)
            ?? TextIntentUtils::extractReferenceAfterKeywords($text, [
                'product',
                'produit',
                'for',
                'pour',
                'of',
                'de',
            ]);

        $componentRef = TextIntentUtils::extractSkuLikeToken($text)
            ?? TextIntentUtils::extractReferenceAfterKeywords($text, [
                'component',
                'composant',
                'part',
                'piece',
                'pièce',
                'for',
                'pour',
                'of',
                'de',
            ]);

        if (TextIntentUtils::containsAny($text, [
            'purchase lots',
            'purchase lot',
            'buying history',
            'purchase history',
            'procurement history',
            'lots purchased',
            'lots for product',
            'achats',
            'historique achat',
            'historique des achats',
        ])) {
            return TextIntentUtils::result('purchase_lots_by_product', [
                'product_id' => $productId ?? $genericId,
                'product_ref' => $productRef,
            ], ($productId ?? $genericId ?? $productRef) ? 'high' : 'medium');
        }

        if (TextIntentUtils::containsAny($text, [
            'stock movements',
            'stock movement',
            'stock history',
            'movement history',
            'inventory movements',
            'movements for component',
            'mouvements stock',
            'historique stock',
            'historique de stock',
        ])) {
            return TextIntentUtils::result('stock_movements_by_component', [
                'component_id' => $componentId ?? $genericId,
                'component_ref' => $componentRef,
            ], ($componentId ?? $genericId ?? $componentRef) ? 'high' : 'medium');
        }

        return null;
    }
}