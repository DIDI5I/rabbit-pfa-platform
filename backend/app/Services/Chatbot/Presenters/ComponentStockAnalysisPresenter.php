<?php

namespace App\Services\Chatbot\Presenters;

class ComponentStockAnalysisPresenter implements ResponsePresenterInterface
{
    public function supports(string $tool): bool
    {
        return $tool === 'component_stock_analysis';
    }

    public function present(array $toolResult, array $identity): array
    {
        if (($toolResult['status'] ?? null) === 'not_found') {
            $componentId = $toolResult['data']['component_id'] ?? null;

            return [
                'message' => 'Component stock not found.',
                'data' => [
                    'answer' => "I could not find stock information for component #{$componentId}.",
                    'intent' => 'component_stock_analysis',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'read_only',
                    'summary' => [
                        'component_id' => $componentId,
                    ],
                    'items_preview' => [],
                    'result_meta' => [],
                    'sources' => [
                        [
                            'tool' => 'component_stock_analysis',
                            'status' => 'not_found',
                        ],
                    ],
                    'limitations' => [],
                    'suggested_actions' => [
                        'Show inventory alerts',
                    ],
                    'ai_refined' => false,
                ],
            ];
        }

        $summary = $toolResult['data']['summary'] ?? [];
        $movements = $toolResult['data']['recent_movements'] ?? [];

        $componentId = $summary['component_id'] ?? null;
        $currentStock = $summary['current_stock'] ?? null;
        $threshold = $summary['low_stock_threshold'] ?? null;
        $status = $summary['stock_status'] ?? 'UNKNOWN';
        $risk = $summary['risk_level'] ?? 'unknown';
        $action = $summary['recommended_action'] ?? 'UNKNOWN';

        return [
            'message' => 'Component stock analysis generated successfully.',
            'data' => [
                'answer' => "Component #{$componentId} has current stock {$currentStock}, threshold {$threshold}, status {$status}, and risk level {$risk}. Recommended action: {$action}.",
                'intent' => 'component_stock_analysis',
                'confidence' => 'high',
                'role' => $identity['role'] ?? 'guest',
                'operation_type' => 'read_only',
                'summary' => $summary,
                'items_preview' => $movements,
                'result_meta' => [
                    'has_more_movements' => $toolResult['meta']['has_more_movements'] ?? false,
                    'movement_count_total' => $toolResult['meta']['movement_count_total'] ?? count($movements),
                ],
                'sources' => [
                    [
                        'tool' => 'component_stock_analysis',
                        'status' => 'used',
                    ],
                    [
                        'tool' => 'stock_summary',
                        'status' => 'used',
                    ],
                    [
                        'tool' => 'stock_movements',
                        'status' => 'used',
                    ],
                ],
                'limitations' => [
                    'This analysis uses current stock, threshold, and recent stock movements. Full forecasting signals may require stock intelligence endpoints.',
                ],
                'suggested_actions' => [
                    'Show stock movements',
                    'Show inventory alerts',
                    'Show reorder recommendations',
                ],
                'ai_refined' => false,
            ],
        ];
    }
}