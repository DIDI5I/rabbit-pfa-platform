<?php

namespace App\Services\Chatbot;

class NavigationResolver
{
    public function resolve(string $target, string $role, ?string $previousPageLink = null): array
    {
        $targets = $this->targets();

        if (!isset($targets[$target])) {
            return [
                'status' => 'not_found',
                'data' => [
                    'requested_target' => $target,
                ],
                'errors' => [
                    [
                        'code' => 'navigation_target_not_found',
                        'message' => 'No matching navigation target exists.',
                    ],
                ],
            ];
        }

        $definition = $targets[$target];

        if (!in_array($role, $definition['roles'], true)) {
            return [
                'status' => 'permission_denied',
                'data' => [
                    'requested_target' => $target,
                    'allowed_targets' => $this->allowedTargetKeys($role),
                ],
                'errors' => [
                    [
                        'code' => 'navigation_forbidden',
                        'message' => 'This navigation target is not available for your role.',
                    ],
                ],
            ];
        }

        return [
            'status' => 'success',
            'data' => [
                'navigation' => [
                    'navigation_type' => 'internal_page',
                    'target_key' => $target,
                    'target_page' => $definition['page'],
                    'previous_page_link' => $previousPageLink,
                    'bubble_text' => 'Back to previous page',
                    'bubble_duration_ms' => 4000,
                ],
            ],
            'errors' => [],
        ];
    }

    public function allowedTargetKeys(string $role): array
    {
        $allowed = [];

        foreach ($this->targets() as $key => $definition) {
            if (in_array($role, $definition['roles'], true)) {
                $allowed[] = $key;
            }
        }

        return $allowed;
    }

    private function targets(): array
    {
        return [
            'login' => [
                'roles' => ['guest'],
                'page' => '/login',
            ],
            'register' => [
                'roles' => ['guest'],
                'page' => '/register',
            ],
            'public_catalog' => [
                'roles' => ['guest', 'client', 'supplier', 'owner'],
                'page' => '/catalog',
            ],

            'owner_dashboard' => [
                'roles' => ['owner'],
                'page' => '/owner/dashboard',
            ],
            'owner_inventory' => [
                'roles' => ['owner'],
                'page' => '/owner/inventory',
            ],
            'owner_stock_intelligence' => [
                'roles' => ['owner'],
                'page' => '/owner/stock-intelligence',
            ],
            'owner_products' => [
                'roles' => ['owner'],
                'page' => '/owner/products',
            ],

            'client_catalog' => [
                'roles' => ['client', 'owner'],
                'page' => '/client/catalog',
            ],
            'client_orders' => [
                'roles' => ['client'],
                'page' => '/client/orders',
            ],
            'client_notifications' => [
                'roles' => ['client'],
                'page' => '/client/notifications',
            ],
            'client_promotions' => [
                'roles' => ['client', 'owner'],
                'page' => '/client/promotions',
            ],

            'supplier_rfqs' => [
                'roles' => ['supplier'],
                'page' => '/supplier/rfqs',
            ],
            'supplier_quotes' => [
                'roles' => ['supplier'],
                'page' => '/supplier/quotes',
            ],
            'supplier_notifications' => [
                'roles' => ['supplier'],
                'page' => '/supplier/notifications',
            ],
        ];
    }
}