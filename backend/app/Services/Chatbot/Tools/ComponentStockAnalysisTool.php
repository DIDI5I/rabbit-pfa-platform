<?php

namespace App\Services\Chatbot\Tools;

use App\Services\StockService;
use App\Repositories\StockMovementRepository;

class ComponentStockAnalysisTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'component_stock_analysis';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $componentId = (int) ($params['component_id'] ?? 0);

        if ($componentId <= 0) {
            return [
                'message' => 'Invalid component.',
                'data' => [
                    'answer' => 'Please provide a valid component ID.',
                    'intent' => 'component_stock_analysis',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'read_only',
                    'summary' => [],
                    'items_preview' => [],
                    'result_meta' => [],
                    'sources' => [
                        [
                            'tool' => 'component_stock_analysis',
                            'status' => 'invalid_component_id',
                        ],
                    ],
                    'limitations' => [],
                    'suggested_actions' => [],
                    'ai_refined' => false,
                ],
            ];
        }

        $stockResponse = (new StockService())->getStock($componentId);
        $stock = $stockResponse['data'] ?? null;

        if (!is_array($stock)) {
            return [
                'tool' => 'component_stock_analysis',
                'status' => 'not_found',
                'data' => [
                    'component_id' => $componentId,
                ],
                'meta' => [
                    'intent' => 'component_stock_analysis',
                    'operation_type' => 'read_only',
                ],
            ];
        }

        $movements = (new StockMovementRepository())->findByComponent($componentId);
        $recentMovements = array_slice($movements, 0, 5);

        $currentStock = (float) ($stock['current_stock'] ?? 0);
        $threshold = (float) ($stock['low_stock_threshold'] ?? 0);
        $status = (string) ($stock['stock_status'] ?? 'UNKNOWN');

        $riskLevel = match ($status) {
            'OUT_OF_STOCK' => 'critical',
            'LOW_STOCK' => 'high',
            default => $currentStock <= ($threshold * 1.5) ? 'medium' : 'low',
        };

        $recommendedAction = match ($status) {
            'OUT_OF_STOCK', 'LOW_STOCK' => 'CREATE_RFQ_OR_REORDER',
            default => $riskLevel === 'medium' ? 'MONITOR_STOCK' : 'NO_IMMEDIATE_ACTION',
        };

        return [
            'tool' => 'component_stock_analysis',
            'status' => 'success',
            'data' => [
                'summary' => [
                    'component_id' => $componentId,
                    'name' => $stock['name'] ?? null,
                    'sku' => $stock['sku'] ?? null,
                    'category' => $stock['category'] ?? null,
                    'current_stock' => $currentStock,
                    'low_stock_threshold' => $threshold,
                    'stock_status' => $status,
                    'risk_level' => $riskLevel,
                    'recommended_action' => $recommendedAction,
                    'recent_movement_count_shown' => count($recentMovements),
                ],
                'recent_movements' => array_map(
                    fn (array $movement): array => [
                        'movement_id' => $movement['id'] ?? null,
                        'type' => $movement['type'] ?? null,
                        'quantity' => $movement['quantity'] ?? null,
                        'reason' => $movement['reason'] ?? null,
                        'reference_type' => $movement['reference_type'] ?? null,
                        'reference_id' => $movement['reference_id'] ?? null,
                        'created_at' => $movement['created_at'] ?? null,
                    ],
                    $recentMovements
                ),
            ],
            'meta' => [
                'intent' => 'component_stock_analysis',
                'operation_type' => 'read_only',
                'has_more_movements' => count($movements) > count($recentMovements),
                'movement_count_total' => count($movements),
            ],
        ];
    }
}