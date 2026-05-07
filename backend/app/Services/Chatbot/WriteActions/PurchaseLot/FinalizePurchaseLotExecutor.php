<?php

namespace App\Services\Chatbot\WriteActions\PurchaseLot;

use App\Core\Auth;
use App\Services\Chatbot\WriteActions\Contracts\PendingActionExecutorInterface;
use App\Services\PurchaseLotService;
use Throwable;

class FinalizePurchaseLotExecutor implements PendingActionExecutorInterface
{
    public function supports(array $action): bool
    {
        return ($action['tool'] ?? null) === 'finalize_purchase_lot';
    }

    public function execute(array $identity, array $action): array
    {
        $purchaseLotId = (int) ($action['params']['purchase_lot_id'] ?? 0);

        $costData = [
            'transport_cost' => (float) ($action['params']['transport_cost'] ?? 0),
            'customs_cost' => (float) ($action['params']['customs_cost'] ?? 0),
            'handling_cost' => (float) ($action['params']['handling_cost'] ?? 0),
            'packaging_cost' => (float) ($action['params']['packaging_cost'] ?? 0),
            'order_preparation_cost' => (float) ($action['params']['order_preparation_cost'] ?? 0),
            'other_cost' => (float) ($action['params']['other_cost'] ?? 0),
        ];

        try {
            $result = (new PurchaseLotService())->finalize(
                $purchaseLotId,
                $costData,
                Auth::id()
            );

            return [
                'executed' => true,
                'answer' => "Confirmed. Purchase lot #{$purchaseLotId} was finalized and stock was received.",
                'summary' => [
                    'updated' => true,
                    'action' => 'finalize_purchase_lot',
                    'purchase_lot_id' => $purchaseLotId,
                    'extra_costs' => $costData,
                    'stock_impact' => 'purchase_received_stock_in_created_if_not_already_received',
                    'service_message' => $result['message'] ?? null,
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'finalize_purchase_lot',
                        'status' => 'executed',
                    ],
                ],
                'limitations' => [],
                'suggested_actions' => [
                    'Show purchase lot details',
                    'Show stock for component',
                    'Show purchase lots',
                ],
            ];
        } catch (Throwable $e) {
            return [
                'executed' => false,
                'answer' => "Purchase lot #{$purchaseLotId} could not be finalized.",
                'summary' => [
                    'updated' => false,
                    'action' => 'finalize_purchase_lot',
                    'purchase_lot_id' => $purchaseLotId,
                    'extra_costs' => $costData,
                    'error' => $e->getMessage(),
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'finalize_purchase_lot',
                        'status' => 'execution_failed',
                    ],
                ],
                'limitations' => [
                    'The purchase lot was not finalized.',
                ],
                'suggested_actions' => [
                    'Show purchase lot details',
                ],
            ];
        }
    }
}