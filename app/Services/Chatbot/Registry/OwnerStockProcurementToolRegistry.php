<?php

namespace App\Services\Chatbot\Registry;

class OwnerStockProcurementToolRegistry implements ToolGroupInterface
{
    public function tools(): array
    {
        return [
            'purchase_lots_by_product' => [
                'roles' => ['owner'],
                'tool' => 'purchase_lots_by_product',
                'operation_type' => 'read_only',
                'sensitive' => true,
                'required_params_any' => ['product_id', 'product_ref'],
            ],

            'stock_movements_by_component' => [
                'roles' => ['owner'],
                'tool' => 'stock_movements_by_component',
                'operation_type' => 'read_only',
                'sensitive' => true,
                'required_params_any' => ['component_id', 'component_ref'],
            ],
        ];
    }
}