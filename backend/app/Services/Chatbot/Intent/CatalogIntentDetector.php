<?php

namespace App\Services\Chatbot\Intent;

class CatalogIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        if (!TextIntentUtils::containsAny($text, [
            'do you have',
            'do u have',
            'available products',
            'what products',
            'what can i buy',
            'catalogue',
            'catalog',
            'browse products',
            'find products',
            'search products',
            'looking for',
            'i need',
            'i want',
            'pumps',
            'pump',
            'hydraulic',
            'maintenance kit',
            'spare parts',
            'pièces',
            'produits disponibles',
            'vous avez',
            'je cherche',
            'product',
            'products',
            'produit',
            'produits',
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

        if ($productId || $genericId) {
            return TextIntentUtils::result('catalog_product_details', [
                'product_id' => $productId ?? $genericId,
            ]);
        }

        return TextIntentUtils::result('catalog_search', [
            'search' => TextIntentUtils::extractSearchTerm($text),
        ]);
    }
}