<?php

namespace App\Services\Chatbot\WriteActions\Stock;

class StockWriteToolRegistry
{
    public function tools(): array
    {
        return [
            'record_stock_movement' => [
                'roles' => ['owner'],
                'tool' => 'record_stock_movement',
                'operation_type' => 'write_action',
                'sensitive' => true,
                'required_params' => ['component_id', 'type', 'quantity', 'reason'],
            ],

            'set_stock_level' => [
                'roles' => ['owner'],
                'tool' => 'set_stock_level',
                'operation_type' => 'write_action',
                'sensitive' => true,
                'required_params' => ['component_id', 'target_stock', 'reason'],
            ],
        ];
    }
}