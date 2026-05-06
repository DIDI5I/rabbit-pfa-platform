<?php

namespace App\Services\Chatbot\Intent;

class NavigationIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        if (!TextIntentUtils::containsAny($text, [
            'open',
            'go to',
            'navigate',
            'take me to',
            'ouvrir',
            'aller à',
        ])) {
            return null;
        }

        $target = null;

        if (TextIntentUtils::containsAny($text, ['inventory', 'inventaire'])) {
            $target = 'owner_inventory';
        } elseif (TextIntentUtils::containsAny($text, ['dashboard', 'tableau de bord'])) {
            $target = 'owner_dashboard';
        } elseif (TextIntentUtils::containsAny($text, ['stock intelligence', 'reorder', 'restock'])) {
            $target = 'owner_stock_intelligence';
        } elseif (TextIntentUtils::containsAny($text, ['products', 'product', 'catalog', 'catalogue', 'produits'])) {
            $target = 'client_catalog';
        } elseif (TextIntentUtils::containsAny($text, ['promotions', 'promo'])) {
            $target = 'client_promotions';
        } elseif (TextIntentUtils::containsAny($text, ['orders', 'commandes'])) {
            $target = 'client_orders';
        } elseif (TextIntentUtils::containsAny($text, ['rfq', 'rfqs', 'devis'])) {
            $target = 'supplier_rfqs';
        } elseif (TextIntentUtils::containsAny($text, ['login', 'connexion'])) {
            $target = 'login';
        } elseif (TextIntentUtils::containsAny($text, ['register', 'signup', 'create account', 'inscription'])) {
            $target = 'register';
        }

        if (!$target) {
            return TextIntentUtils::result('navigate', [], 'low');
        }

        return TextIntentUtils::result('navigate', [
            'target' => $target,
        ]);
    }
}