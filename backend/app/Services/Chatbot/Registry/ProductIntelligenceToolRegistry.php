<?php

namespace App\Services\Chatbot\Registry;

class ProductIntelligenceToolRegistry
{
    public function tools(): array
    {
        return [
            'product_intelligence_snapshot' => [
                'roles' => ['owner', 'client', 'supplier', 'fournisseur', 'guest'],
                'tool' => 'product_intelligence_snapshot',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params_any' => ['product_id', 'product_ref'],
            ],
        ];
    }
}