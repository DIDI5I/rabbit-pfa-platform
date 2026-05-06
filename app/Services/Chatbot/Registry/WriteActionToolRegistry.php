<?php

namespace App\Services\Chatbot\Registry;

class WriteActionToolRegistry
{
    public function tools(): array
    {
        return [
            'confirm_write_action' => [
                'roles' => ['owner', 'client', 'supplier', 'fournisseur'],
                'tool' => 'confirm_write_action',
                'operation_type' => 'write_action',
                'sensitive' => true,
                'required_params' => [],
            ],

            'cancel_write_action' => [
                'roles' => ['owner', 'client', 'supplier', 'fournisseur', 'guest'],
                'tool' => 'cancel_write_action',
                'operation_type' => 'write_action',
                'sensitive' => false,
                'required_params' => [],
            ],
        ];
    }
}