<?php

namespace App\Services\Chatbot\Registry;

class OwnerProductToolRegistry implements ToolGroupInterface
{
    public function tools(): array
    {
        return [
            'owner_product_details' => [
                'roles' => ['owner'],
                'tool' => 'owner_product_details',
                'operation_type' => 'read_only',
                'sensitive' => true,
                'required_params_any' => ['product_id', 'product_ref'],
            ],

            'owner_product_dependencies' => [
                'roles' => ['owner'],
                'tool' => 'owner_product_dependencies',
                'operation_type' => 'read_only',
                'sensitive' => true,
                'required_params_any' => ['product_id', 'product_ref'],
            ],
        ];
    }
}