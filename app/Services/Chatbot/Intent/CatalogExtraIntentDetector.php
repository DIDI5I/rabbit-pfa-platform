<?php

namespace App\Services\Chatbot\Intent;

class CatalogExtraIntentDetector implements IntentDetectorInterface
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

        if (TextIntentUtils::containsAny($text, [
            'related to',
            'relations',
            'relation',
            'compatible',
            'compatibility',
            'alternatives',
            'alternative',
            'similar products',
            'related products',
            'dependencies',
            'depends on',
            'lié',
            'lies',
            'compatible avec',
        ])) {
            return TextIntentUtils::result('catalog_product_relations', [
                'product_id' => $id,
            ], $id ? 'high' : 'medium');
        }

        if (TextIntentUtils::containsAny($text, [
            'promotion for product',
            'promotions for product',
            'promo for product',
            'discount for product',
            'offer for product',
            'is product',
            'does product',
            'on promotion',
            'on sale',
            'remise pour',
            'promo pour',
        ])) {
            return TextIntentUtils::result('catalog_product_promotions', [
                'product_id' => $id,
            ], $id ? 'high' : 'medium');
        }

        if (TextIntentUtils::containsAny($text, [
            'reviews for',
            'review for',
            'what do people say',
            'what users say',
            'customer reviews',
            'user reviews',
            'avis',
            'commentaires',
        ])) {
            return TextIntentUtils::result('catalog_product_reviews', [
                'product_id' => $id,
            ], $id ? 'high' : 'medium');
        }

        if (TextIntentUtils::containsAny($text, [
            'rating',
            'ratings',
            'rating summary',
            'average rating',
            'how is product rated',
            'stars',
            'note moyenne',
            'évaluation',
            'evaluation',
        ])) {
            return TextIntentUtils::result('catalog_product_rating_summary', [
                'product_id' => $id,
            ], $id ? 'high' : 'medium');
        }

        return null;
    }
}