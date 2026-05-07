<?php

namespace App\Services\Chatbot\WriteActions\PurchaseLot;

class PurchaseLotWriteToolRegistry
{
    public function tools(): array
    {
        return [
            'finalize_purchase_lot' => [
                'roles' => ['owner'],
                'tool' => 'finalize_purchase_lot',
                'operation_type' => 'write_action',
                'sensitive' => true,
                'required_params' => ['purchase_lot_id'],
            ],

            'update_pending_purchase_lot_costs' => [
                'roles' => ['owner'],
                'tool' => 'update_pending_purchase_lot_costs',
                'operation_type' => 'write_action',
                'sensitive' => true,
                'required_params' => [],
            ],
        ];
    }
}