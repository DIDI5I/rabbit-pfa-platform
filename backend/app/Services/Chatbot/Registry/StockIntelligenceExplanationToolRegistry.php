<?php

namespace App\Services\Chatbot\Registry;

class StockIntelligenceExplanationToolRegistry
{
    public function tools(): array
    {
        return [
            'stock_intelligence_explanation' => [
                'roles' => ['owner'],
                'tool' => 'stock_intelligence_explanation',
                'operation_type' => 'read_only',
                'sensitive' => true,
                'required_params_any' => ['product_id', 'product_ref'],
            ],

            'component_stock_analysis' => [
                'roles' => ['owner'],
                'tool' => 'component_stock_analysis',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => ['component_id'],
            ],
        ];
    }
}