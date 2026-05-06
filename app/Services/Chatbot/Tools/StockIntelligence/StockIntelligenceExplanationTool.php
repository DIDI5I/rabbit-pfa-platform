<?php

namespace App\Services\Chatbot\Tools\StockIntelligence;

use App\Services\Chatbot\EntityResolver;
use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\StockIntelligenceService;
use Throwable;

class StockIntelligenceExplanationTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'stock_intelligence_explanation';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';

        try {
            $resolution = (new EntityResolver())->resolveProduct($params);

            if (($resolution['status'] ?? null) !== 'resolved') {
                return $this->result(
                    'stock_intelligence_explanation',
                    $resolution['status'] ?? 'entity_resolution_failed',
                    [
                        'resolution' => $resolution,
                    ],
                    $this->meta('stock_intelligence_explanation', $role, 'read_only', true, 0),
                    [
                        [
                            'code' => 'entity_resolution_' . ($resolution['status'] ?? 'failed'),
                            'message' => 'Could not resolve the product reference.',
                        ],
                    ]
                );
            }

            $productId = (int) $resolution['id'];

            $response = (new StockIntelligenceService())->reorderRecommendations();
            $data = $response['data'] ?? [];

            $items = is_array($data['items'] ?? null) ? $data['items'] : [];
            $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];

            $item = $this->findItem($items, $productId);

            if (!$item) {
                return $this->result(
                    'stock_intelligence_explanation',
                    'not_found',
                    [
                        'product_id' => $productId,
                        'resolution' => $resolution,
                    ],
                    $this->meta('stock_intelligence_explanation', $role, 'read_only', true, 0),
                    [
                        [
                            'code' => 'stock_intelligence_item_not_found',
                            'message' => 'No stock intelligence item was found for this product.',
                        ],
                    ]
                );
            }

            return $this->result(
                'stock_intelligence_explanation',
                'success',
                [
                    'product_id' => $productId,
                    'resolution' => $resolution,
                    'item' => $item,
                    'engine' => [
                        'period_days' => $data['period_days'] ?? null,
                        'forecast_days' => $data['forecast_days'] ?? null,
                        'calculation_mode' => $data['calculation_mode'] ?? null,
                        'summary' => $summary,
                    ],
                    'count' => 1,
                ],
                $this->meta('stock_intelligence_explanation', $role, 'read_only', true, 1)
            );
        } catch (Throwable $e) {
            return $this->result(
                'stock_intelligence_explanation',
                'failed',
                [],
                $this->meta('stock_intelligence_explanation', $role, 'read_only', true, 0),
                [
                    [
                        'code' => 'stock_intelligence_explanation_failed',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }

    private function findItem(array $items, int $productId): ?array
    {
        foreach ($items as $item) {
            $itemProductId = (int) ($item['product_id'] ?? 0);

            if ($itemProductId === $productId) {
                return $item;
            }
        }

        return null;
    }
}