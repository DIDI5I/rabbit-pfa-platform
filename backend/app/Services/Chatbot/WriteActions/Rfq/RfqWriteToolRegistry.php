<?php

namespace App\Services\Chatbot\WriteActions\Rfq;

class RfqWriteToolRegistry
{
    public function tools(): array
    {
        return [
            'reject_rfq' => [
                'roles' => ['owner'],
                'tool' => 'reject_rfq',
                'operation_type' => 'write_action',
                'sensitive' => true,
                'required_params' => ['rfq_id'],
            ],

            'accept_rfq' => [
                'roles' => ['owner'],
                'tool' => 'accept_rfq',
                'operation_type' => 'write_action',
                'sensitive' => true,
                'required_params' => ['rfq_id'],
            ],
        ];
    }
}