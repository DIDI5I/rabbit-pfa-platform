<?php

namespace App\Services\Chatbot\WriteActions\PurchaseLot;

use App\Repositories\PurchaseLotRepository;
use App\Repositories\StockMovementRepository;
use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\Chatbot\WriteActions\PendingActionStore;
use App\Services\Chatbot\WriteActions\WriteActionResponseBuilder;

class UpdatePendingPurchaseLotCostsTool extends BaseToolHandler
{
    private const COST_FIELDS = [
        'transport_cost',
        'customs_cost',
        'handling_cost',
        'packaging_cost',
        'order_preparation_cost',
        'other_cost',
    ];

    public function supports(string $intent): bool
    {
        return $intent === 'update_pending_purchase_lot_costs';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $store = new PendingActionStore();
        $pending = $store->get($identity);

        if (!is_array($pending) || ($pending['tool'] ?? null) !== 'finalize_purchase_lot') {
            return [
                'message' => 'No pending purchase lot finalization.',
                'data' => [
                    'answer' => 'There is no pending purchase lot finalization to update.',
                    'ai_refined' => false,
                    'intent' => 'update_pending_purchase_lot_costs',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'write_action',
                    'summary' => [
                        'pending_action' => false,
                        'updated' => false,
                    ],
                    'items_preview' => [],
                    'result_meta' => [
                        'pending_action' => false,
                        'executed' => false,
                    ],
                    'sources' => [
                        [
                            'tool' => 'update_pending_purchase_lot_costs',
                            'status' => 'no_pending_finalize_purchase_lot',
                        ],
                    ],
                    'limitations' => [
                        'Start by asking to finalize a purchase lot first.',
                    ],
                    'suggested_actions' => [
                        'Finalize purchase lot',
                    ],
                ],
            ];
        }

        $updatedParams = $pending['params'] ?? [];

        foreach (self::COST_FIELDS as $field) {
            if (array_key_exists($field, $params)) {
                $updatedParams[$field] = max(0.0, (float) $params[$field]);
            }
        }

        $purchaseLotId = (int) ($updatedParams['purchase_lot_id'] ?? 0);

        $repository = new PurchaseLotRepository();
        $lot = $repository->findById($purchaseLotId);

        if (!is_array($lot)) {
            return [
                'message' => 'Purchase lot not found.',
                'data' => [
                    'answer' => "Purchase lot #{$purchaseLotId} was not found.",
                    'ai_refined' => false,
                    'intent' => 'update_pending_purchase_lot_costs',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'write_action',
                    'summary' => [
                        'pending_action' => true,
                        'updated' => false,
                        'purchase_lot_id' => $purchaseLotId,
                    ],
                    'items_preview' => [],
                    'result_meta' => [
                        'pending_action' => true,
                        'executed' => false,
                    ],
                    'sources' => [
                        [
                            'tool' => 'update_pending_purchase_lot_costs',
                            'status' => 'purchase_lot_not_found',
                        ],
                    ],
                    'limitations' => [],
                    'suggested_actions' => [
                        'Cancel',
                    ],
                ],
            ];
        }

        $costs = $this->extractCosts($updatedParams);
        $quantity = (float) ($lot['quantity_received'] ?? 0);
        $supplierTotal = (float) ($lot['supplier_total_price'] ?? 0);

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

        $unitPurchaseCost = $quantity > 0
            ? round($totalPurchaseCost / $quantity, 4)
            : 0.0;

        $stockRepository = new StockMovementRepository();
        $currentStock = $stockRepository->currentStock((int) $lot['component_id']);
        $alreadyReceived = $repository->hasPurchaseReceivedStockMovement($purchaseLotId);

        $updatedPending = $pending;
        $updatedPending['params'] = array_merge($updatedParams, $costs);
        $updatedPending['description'] = "I can finalize purchase lot #{$purchaseLotId}. Extra costs are currently " . array_sum($costs) . ".";
        $updatedPending['preview'] = [
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
                    'estimated_after_stock' => $alreadyReceived ? $currentStock : $currentStock + $quantity,
                ],
                'side_effects' => [
                    'purchase_lot_finalized',
                    $alreadyReceived ? 'stock_already_received_no_duplicate_movement' : 'purchase_received_stock_in_created',
                    'owner_notified',
                ],
            ],
        ];

        $store->put($identity, $updatedPending);
        $freshPending = $store->get($identity);

        return (new WriteActionResponseBuilder())->preview(
            $identity,
            $freshPending ?? $updatedPending
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
}