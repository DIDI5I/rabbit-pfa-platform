<?php

namespace App\Services\Chatbot;

class RoleProfileRegistry
{
    public function get(string $role): array
    {
        $profiles = [
            'guest' => [
                'role' => 'guest',
                'label' => 'Guest',
                'available_topics' => [
                    'public catalogue',
                    'login',
                    'register',
                ],
                'restricted_topics' => [
                    'inventory',
                    'orders',
                    'RFQs',
                    'cost rollup',
                    'dashboard',
                ],
                'suggested_actions' => [
                    'login',
                    'register',
                    'browse catalogue',
                ],
            ],

            'client' => [
                'role' => 'client',
                'label' => 'Client',
                'available_topics' => [
                    'catalogue products',
                    'product details',
                    'active promotions',
                ],
                'restricted_topics' => [
                    'inventory',
                    'stock intelligence',
                    'cost rollup',
                    'purchase lots',
                    'owner dashboard',
                ],
                'suggested_actions' => [
                    'Ask about catalogue products',
                    'Ask about active promotions',
                    'Open catalogue',
                ],
            ],

            'supplier' => [
                'role' => 'supplier',
                'label' => 'Supplier',
                'available_topics' => [
                    'catalogue products',
                    'supplier pages',
                ],
                'restricted_topics' => [
                    'owner inventory',
                    'cost rollup',
                    'stock intelligence',
                    'client private data',
                ],
                'suggested_actions' => [
                    'Open supplier RFQs',
                    'Open supplier notifications',
                ],
            ],

            'owner' => [
                'role' => 'owner',
                'label' => 'Owner',
                'available_topics' => [
                    'inventory',
                    'inventory alerts',
                    'cost rollup',
                    'reorder recommendations',
                    'stock dashboard',
                    'catalogue products',
                    'active promotions',
                ],
                'restricted_topics' => [],
                'suggested_actions' => [
                    'Show inventory alerts',
                    'What should I reorder?',
                    'Show cost rollup for product 1',
                    'Open inventory page',
                ],
            ],
        ];

        return $profiles[$role] ?? $profiles['guest'];
    }
}