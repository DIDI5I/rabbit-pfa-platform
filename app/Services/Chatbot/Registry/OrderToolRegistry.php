<?php

namespace App\Services\Chatbot\Registry;

class OrderToolRegistry
{
    public function tools(): array
    {
        return [
            'order_summary' => [
                'roles' => ['owner', 'client'],
                'tool' => 'order_summary',
                'operation_type' => 'read_only',
                'sensitive' => true,
                'required_params' => [],
            ],

            'recent_orders' => [
                'roles' => ['owner', 'client'],
                'tool' => 'recent_orders',
                'operation_type' => 'read_only',
                'sensitive' => true,
                'required_params' => [],
            ],

            'orders_by_status' => [
                'roles' => ['owner', 'client'],
                'tool' => 'orders_by_status',
                'operation_type' => 'read_only',
                'sensitive' => true,
                'required_params' => ['status'],
            ],

            'order_details' => [
                'roles' => ['owner', 'client'],
                'tool' => 'order_details',
                'operation_type' => 'read_only',
                'sensitive' => true,
                'required_params' => ['order_id'],
            ],
        ];
    }
}