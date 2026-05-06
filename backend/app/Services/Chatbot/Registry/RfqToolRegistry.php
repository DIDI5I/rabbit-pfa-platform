<?php

namespace App\Services\Chatbot\Registry;

class RfqToolRegistry
{
    public function tools(): array
    {
        return [
            'rfq_summary' => [
                'roles' => ['owner', 'supplier', 'fournisseur'],
                'tool' => 'rfq_summary',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => [],
            ],

            'rfq_details' => [
                'roles' => ['owner', 'supplier', 'fournisseur'],
                'tool' => 'rfq_details',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => ['rfq_id'],
            ],

            'rfqs_by_status' => [
                'roles' => ['owner', 'supplier', 'fournisseur'],
                'tool' => 'rfqs_by_status',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => ['status'],
            ],

            'rfq_allowed_actions' => [
                'roles' => ['owner', 'supplier', 'fournisseur'],
                'tool' => 'rfq_allowed_actions',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => ['rfq_id'],
            ],
        ];
    }
}