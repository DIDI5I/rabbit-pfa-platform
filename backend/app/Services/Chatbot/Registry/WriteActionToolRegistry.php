<?php

namespace App\Services\Chatbot\Registry;

use App\Services\Chatbot\WriteActions\Notification\NotificationWriteToolRegistry;
use App\Services\Chatbot\WriteActions\Rfq\RfqWriteToolRegistry;

class WriteActionToolRegistry
{
    public function tools(): array
    {
            return array_merge(
        [
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
        ],
        (new \App\Services\Chatbot\WriteActions\Notification\NotificationWriteToolRegistry())->tools(),
        (new RfqWriteToolRegistry())->tools()
    );
    }
}