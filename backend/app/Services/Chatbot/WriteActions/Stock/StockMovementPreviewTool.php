<?php

namespace App\Services\Chatbot\WriteActions\Stock;

use App\Repositories\StockMovementRepository;
use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\Chatbot\WriteActions\PendingActionStore;
use App\Services\Chatbot\WriteActions\WriteActionResponseBuilder;

class StockMovementPreviewTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'record_stock_movement';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $componentId = (int) ($params['component_id'] ?? 0);
        $type = strtolower(trim((string) ($params['type'] ?? '')));
        $quantity = (float) ($params['quantity'] ?? 0);
        $reason = strtoupper(trim((string) ($params['reason'] ?? 'MANUAL_ADJUSTMENT')));

        if (!in_array($type, ['in', 'out'], true)) {
            return [
                'message' => 'Stock action not supported.',
                'data' => [
                    'answer' => "Only stock IN and OUT movements are supported through chatbot right now.",
                    'ai_refined' => false,
                    'intent' => 'record_stock_movement',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'write_action',
                    'summary' => [
                        'action_required' => false,
                        'confirmation_required' => false,
                        'component_id' => $componentId,
                        'requested_type' => $type,
                    ],
                    'items_preview' => [],
                    'result_meta' => [
                        'pending_action' => false,
                        'executed' => false,
                    ],
                    'sources' => [
                        [
                            'tool' => 'record_stock_movement',
                            'status' => 'unsupported_stock_movement_type',
                        ],
                    ],
                    'limitations' => [],
                    'suggested_actions' => [
                        'Show stock for component',
                    ],
                ],
            ];
        }
        if ($quantity <= 0) {
            return [
                'message' => 'Invalid stock quantity.',
                'data' => [
                    'answer' => "Stock quantity must be greater than 0.",
                    'ai_refined' => false,
                    'intent' => 'record_stock_movement',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'write_action',
                    'summary' => [
                        'action_required' => false,
                        'confirmation_required' => false,
                        'component_id' => $componentId,
                        'quantity' => $quantity,
                    ],
                    'items_preview' => [],
                    'result_meta' => [
                        'pending_action' => false,
                        'executed' => false,
                    ],
                    'sources' => [
                        [
                            'tool' => 'record_stock_movement',
                            'status' => 'invalid_quantity',
                        ],
                    ],
                    'limitations' => [],
                    'suggested_actions' => [],
                ],
            ];
        }

        $repository = new StockMovementRepository();
        $summary = $repository->stockSummary($componentId);

        if (!is_array($summary)) {
            return [
                'message' => 'Product not found.',
                'data' => [
                    'answer' => "Component #{$componentId} was not found.",
                    'ai_refined' => false,
                    'intent' => 'record_stock_movement',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'write_action',
                    'summary' => [
                        'action_required' => false,
                        'confirmation_required' => false,
                        'component_id' => $componentId,
                    ],
                    'items_preview' => [],
                    'result_meta' => [
                        'pending_action' => false,
                        'executed' => false,
                    ],
                    'sources' => [
                        [
                            'tool' => 'record_stock_movement',
                            'status' => 'component_not_found',
                        ],
                    ],
                    'limitations' => [],
                    'suggested_actions' => [],
                ],
            ];
        }

        $currentStock = (float) ($summary['current_stock'] ?? 0);
        $threshold = (float) ($summary['low_stock_threshold'] ?? 0);
        $afterStock = $type === 'in'
            ? $currentStock + $quantity
            : $currentStock - $quantity;
        if ($type === 'out' && $currentStock < $quantity) {
        return [
            'message' => 'Insufficient stock.',
            'data' => [
                'answer' => "Cannot record stock OUT for component #{$componentId}. Current stock is {$currentStock}, requested OUT quantity is {$quantity}.",
                'ai_refined' => false,
                'intent' => 'record_stock_movement',
                'confidence' => 'high',
                'role' => $identity['role'] ?? 'guest',
                'operation_type' => 'write_action',
                'summary' => [
                    'action_required' => false,
                    'confirmation_required' => false,
                    'component_id' => $componentId,
                    'movement_type' => 'out',
                    'current_stock' => $currentStock,
                    'requested_quantity' => $quantity,
                    'shortage' => $quantity - $currentStock,
                ],
                'items_preview' => [],
                'result_meta' => [
                    'pending_action' => false,
                    'executed' => false,
                ],
                'sources' => [
                    [
                        'tool' => 'record_stock_movement',
                        'status' => 'insufficient_stock',
                    ],
                ],
                'limitations' => [
                    'Stock OUT cannot exceed current available stock.',
                ],
                'suggested_actions' => [
                    'Show stock for component',
                ],
            ],
        ];
    }
        $afterStatus = $this->resolveStockStatus($afterStock, $threshold);

        $action = [
            'intent' => 'record_stock_movement',
            'tool' => 'record_stock_movement',
            'title' => 'Record stock movement',
            'description' => "I can record a stock {$type} movement of {$quantity} for component #{$componentId}.",
            'params' => [
                'component_id' => $componentId,
                'type' => $type,
                'quantity' => $quantity,
                'reason' => $reason,
                'reference_type' => null,
                'reference_id' => null,
                'notes' => 'Recorded through chatbot manual stock adjustment',
            ],
            'preview' => [
                [
                    'type' => 'stock_movement',
                    'component_id' => $componentId,
                    'component_name' => $summary['name'] ?? null,
                    'component_sku' => $summary['sku'] ?? null,
                    'movement_type' => $type,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'current_stock' => $currentStock,
                    'estimated_after_stock' => $afterStock,
                    'low_stock_threshold' => $threshold,
                    'estimated_after_status' => $afterStatus,
                    'side_effects' => [
                        'stock_movement_created',
                        'stock_alert_checked',
                        $type === 'out' ? 'stock_decreased' : 'stock_increased',
                    ],
                ],
            ],
            'risk_level' => $type === 'out' ? 'high' : 'medium',
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

    private function resolveStockStatus(float $currentStock, float $threshold): string
    {
        if ($currentStock <= 0) {
            return 'OUT_OF_STOCK';
        }

        if ($currentStock <= $threshold) {
            return 'LOW_STOCK';
        }

        return 'OK';
    }
}