<?php

namespace App\Services\Chatbot\WriteActions\Order;

class OrderWriteToolRegistry
{
    public function tools(): array
    {
        return [
            'update_order_status' => [
                'roles' => ['owner'],
                'tool' => 'update_order_status',
                'operation_type' => 'write_action',
                'sensitive' => true,
                'required_params' => ['order_id', 'status'],
            ],
        ];
    }
}