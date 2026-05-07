<?php

namespace App\Services\Chatbot\WriteActions\Stock;

use App\Repositories\StockMovementRepository;
use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\Chatbot\WriteActions\PendingActionStore;
use App\Services\Chatbot\WriteActions\WriteActionResponseBuilder;

class SetStockLevelPreviewTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'set_stock_level';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $componentId = (int) ($params['component_id'] ?? 0);
        $targetStock = (float) ($params['target_stock'] ?? -1);
        $reason = strtoupper(trim((string) ($params['reason'] ?? 'MANUAL_ADJUSTMENT')));

        if ($targetStock < 0) {
            return [
                'message' => 'Invalid stock target.',
                'data' => [
                    'answer' => 'Target stock must be zero or greater.',
                    'ai_refined' => false,
                    'intent' => 'set_stock_level',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'write_action',
                    'summary' => [
                        'action_required' => false,
                        'confirmation_required' => false,
                        'component_id' => $componentId,
                        'target_stock' => $targetStock,
                    ],
                    'items_preview' => [],
                    'result_meta' => [
                        'pending_action' => false,
                        'executed' => false,
                    ],
                    'sources' => [
                        [
                            'tool' => 'set_stock_level',
                            'status' => 'invalid_target_stock',
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
                    'intent' => 'set_stock_level',
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
                            'tool' => 'set_stock_level',
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
        $difference = $targetStock - $currentStock;

        if (abs($difference) < 0.00001) {
            return [
                'message' => 'Stock action not needed.',
                'data' => [
                    'answer' => "Component #{$componentId} stock is already {$targetStock}.",
                    'ai_refined' => false,
                    'intent' => 'set_stock_level',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'write_action',
                    'summary' => [
                        'action_required' => false,
                        'confirmation_required' => false,
                        'component_id' => $componentId,
                        'current_stock' => $currentStock,
                        'target_stock' => $targetStock,
                    ],
                    'items_preview' => [],
                    'result_meta' => [
                        'pending_action' => false,
                        'executed' => false,
                    ],
                    'sources' => [
                        [
                            'tool' => 'set_stock_level',
                            'status' => 'no_action_needed',
                        ],
                    ],
                    'limitations' => [],
                    'suggested_actions' => [
                        'Show stock for component',
                    ],
                ],
            ];
        }

        $movementType = $difference > 0 ? 'in' : 'out';
        $movementQuantity = abs($difference);
        $afterStatus = $this->resolveStockStatus($targetStock, $threshold);

        $action = [
            'intent' => 'set_stock_level',
            'tool' => 'set_stock_level',
            'title' => 'Set stock level',
            'description' => "I can set component #{$componentId} stock to {$targetStock} by recording a stock {$movementType} movement of {$movementQuantity}.",
            'params' => [
                'component_id' => $componentId,
                'target_stock' => $targetStock,
                'movement_type' => $movementType,
                'movement_quantity' => $movementQuantity,
                'reason' => $reason,
                'reference_type' => null,
                'reference_id' => null,
                'notes' => "Set stock level to {$targetStock} through chatbot manual adjustment",
            ],
            'preview' => [
                [
                    'type' => 'stock_level_adjustment',
                    'component_id' => $componentId,
                    'component_name' => $summary['name'] ?? null,
                    'component_sku' => $summary['sku'] ?? null,
                    'current_stock' => $currentStock,
                    'target_stock' => $targetStock,
                    'difference' => $difference,
                    'movement_type' => $movementType,
                    'movement_quantity' => $movementQuantity,
                    'reason' => $reason,
                    'low_stock_threshold' => $threshold,
                    'estimated_after_stock' => $targetStock,
                    'estimated_after_status' => $afterStatus,
                    'side_effects' => [
                        'stock_movement_created',
                        'stock_alert_checked',
                        $movementType === 'out' ? 'stock_decreased' : 'stock_increased',
                    ],
                ],
            ],
            'risk_level' => $movementType === 'out' ? 'high' : 'medium',
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