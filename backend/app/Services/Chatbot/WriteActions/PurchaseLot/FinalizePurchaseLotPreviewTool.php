<?php

namespace App\Services\Chatbot\WriteActions\PurchaseLot;

use App\Repositories\PurchaseLotRepository;
use App\Repositories\StockMovementRepository;
use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\Chatbot\WriteActions\PendingActionStore;
use App\Services\Chatbot\WriteActions\WriteActionResponseBuilder;

class FinalizePurchaseLotPreviewTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'finalize_purchase_lot';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $purchaseLotId = (int) ($params['purchase_lot_id'] ?? 0);

        $costs = $this->extractCosts($params);

        $repository = new PurchaseLotRepository();
        $lot = $repository->findById($purchaseLotId);

        if (!is_array($lot)) {
            return [
                'message' => 'Purchase lot not found.',
                'data' => [
                    'answer' => "Purchase lot #{$purchaseLotId} was not found.",
                    'ai_refined' => false,
                    'intent' => 'finalize_purchase_lot',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'write_action',
                    'summary' => [
                        'action_required' => false,
                        'confirmation_required' => false,
                        'purchase_lot_id' => $purchaseLotId,
                    ],
                    'items_preview' => [],
                    'result_meta' => [
                        'pending_action' => false,
                        'executed' => false,
                    ],
                    'sources' => [
                        [
                            'tool' => 'finalize_purchase_lot',
                            'status' => 'not_found',
                        ],
                    ],
                    'limitations' => [],
                    'suggested_actions' => [
                        'Show purchase lots',
                    ],
                ],
            ];
        }

        if (($lot['status'] ?? null) === 'finalized') {
            return $this->blocked(
                $identity,
                $purchaseLotId,
                'Purchase lot action not needed.',
                "Purchase lot #{$purchaseLotId} is already finalized.",
                'already_finalized',
                $lot
            );
        }

        if (($lot['status'] ?? null) === 'cancelled') {
            return $this->blocked(
                $identity,
                $purchaseLotId,
                'Purchase lot action not allowed.',
                "Purchase lot #{$purchaseLotId} is cancelled and cannot be finalized.",
                'cancelled_lot',
                $lot
            );
        }

        $quantity = (float) ($lot['quantity_received'] ?? 0);
        $supplierTotal = (float) ($lot['supplier_total_price'] ?? 0);

        if ($quantity <= 0) {
            return $this->blocked(
                $identity,
                $purchaseLotId,
                'Purchase lot action not allowed.',
                "Purchase lot #{$purchaseLotId} cannot be finalized because quantity_received is invalid.",
                'invalid_quantity',
                $lot
            );
        }

        $totalPurchaseCost = round(
            $supplierTotal
            + $costs['transport_cost']
            + $costs['customs_cost']
            + $costs['handling_cost']
            + $costs['packaging_cost']
            + $costs['order_preparation_cost']
            + $costs['other_cost'],
            2
        );

        $unitPurchaseCost = round($totalPurchaseCost / $quantity, 4);

        $stockRepository = new StockMovementRepository();
        $currentStock = $stockRepository->currentStock((int) $lot['component_id']);
        $estimatedAfterStock = $currentStock + $quantity;

        $alreadyReceived = $repository->hasPurchaseReceivedStockMovement($purchaseLotId);

        $action = [
            'intent' => 'finalize_purchase_lot',
            'tool' => 'finalize_purchase_lot',
            'title' => 'Finalize purchase lot',
            'description' => "I can finalize purchase lot #{$purchaseLotId}. Extra costs are currently " . $this->totalExtraCosts($costs) . ".",
            'params' => array_merge(
                [
                    'purchase_lot_id' => $purchaseLotId,
                ],
                $costs
            ),
            'preview' => [
                [
                    'type' => 'purchase_lot_finalization',
                    'purchase_lot_id' => $purchaseLotId,
                    'current_status' => $lot['status'] ?? null,
                    'new_status' => 'finalized',
                    'component_id' => $lot['component_id'] ?? null,
                    'component_name' => $lot['component_name'] ?? null,
                    'component_sku' => $lot['component_sku'] ?? null,
                    'supplier_id' => $lot['supplier_id'] ?? null,
                    'supplier_name' => $lot['supplier_name'] ?? null,
                    'quantity_received' => $quantity,
                    'supplier_unit_price' => $lot['supplier_unit_price'] ?? null,
                    'supplier_total_price' => $supplierTotal,
                    'extra_costs' => $costs,
                    'estimated_total_purchase_cost' => $totalPurchaseCost,
                    'estimated_unit_purchase_cost' => $unitPurchaseCost,
                    'stock_impact' => [
                        'type' => $alreadyReceived ? 'none_already_received' : 'stock_in',
                        'reason' => 'PURCHASE_RECEIVED',
                        'quantity' => $quantity,
                        'current_stock' => $currentStock,
                        'estimated_after_stock' => $alreadyReceived ? $currentStock : $estimatedAfterStock,
                    ],
                    'side_effects' => [
                        'purchase_lot_finalized',
                        $alreadyReceived ? 'stock_already_received_no_duplicate_movement' : 'purchase_received_stock_in_created',
                        'owner_notified',
                    ],
                ],
            ],
            'risk_level' => 'high',
            'ttl_seconds' => 300,
        ];

        $store = new PendingActionStore();
        $store->put($identity, $action);

        $pending = $store->get($identity);

        return (new WriteActionResponseBuilder())->preview(
            $identity,
            $pending ?? $action
        );
    }

    private function extractCosts(array $params): array
    {
        return [
            'transport_cost' => (float) ($params['transport_cost'] ?? 0),
            'customs_cost' => (float) ($params['customs_cost'] ?? 0),
            'handling_cost' => (float) ($params['handling_cost'] ?? 0),
            'packaging_cost' => (float) ($params['packaging_cost'] ?? 0),
            'order_preparation_cost' => (float) ($params['order_preparation_cost'] ?? 0),
            'other_cost' => (float) ($params['other_cost'] ?? 0),
        ];
    }

    private function totalExtraCosts(array $costs): float
    {
        return array_sum($costs);
    }

    private function blocked(
        array $identity,
        int $purchaseLotId,
        string $message,
        string $answer,
        string $status,
        array $lot
    ): array {
        return [
            'message' => $message,
            'data' => [
                'answer' => $answer,
                'ai_refined' => false,
                'intent' => 'finalize_purchase_lot',
                'confidence' => 'high',
                'role' => $identity['role'] ?? 'guest',
                'operation_type' => 'write_action',
                'summary' => [
                    'action_required' => false,
                    'confirmation_required' => false,
                    'purchase_lot_id' => $purchaseLotId,
                    'current_status' => $lot['status'] ?? null,
                ],
                'items_preview' => [],
                'result_meta' => [
                    'pending_action' => false,
                    'executed' => false,
                ],
                'sources' => [
                    [
                        'tool' => 'finalize_purchase_lot',
                        'status' => $status,
                    ],
                ],
                'limitations' => [],
                'suggested_actions' => [
                    'Show purchase lot details',
                    'Show purchase lots',
                ],
            ],
        ];
    }
}