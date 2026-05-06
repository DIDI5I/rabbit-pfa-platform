<?php

namespace App\Services\Chatbot\Registry;

class OwnerInventoryToolRegistry implements ToolGroupInterface
{
    public function tools(): array
    {
        return [
            'inventory_summary' => [
                'roles' => ['owner'],
                'tool' => 'inventory_summary',
                'operation_type' => 'read_only',
                'sensitive' => true,
                'required_params' => [],
            ],

            'inventory_alerts' => [
                'roles' => ['owner'],
                'tool' => 'inventory_alerts',
                'operation_type' => 'read_only',
                'sensitive' => true,
                'required_params' => [],
            ],

            'cost_rollup' => [
                'roles' => ['owner'],
                'tool' => 'cost_rollup',
                'operation_type' => 'read_only',
                'sensitive' => true,
                'required_params' => ['product_id'],
            ],

            'reorder_recommendations' => [
                'roles' => ['owner'],
                'tool' => 'reorder_recommendations',
                'operation_type' => 'read_only',
                'sensitive' => true,
                'required_params' => [],
            ],

            'dashboard_stock_summary' => [
                'roles' => ['owner'],
                'tool' => 'dashboard_stock_summary',
                'operation_type' => 'read_only',
                'sensitive' => true,
                'required_params' => [],
            ],
        ];
    }
}