<?php

namespace App\Services\Chatbot\Tools\ProductIntelligence;

use App\Services\Chatbot\EntityResolver;
use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\ProductIntelligenceSnapshotService;
use Throwable;

class ProductIntelligenceSnapshotTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'product_intelligence_snapshot';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';

        try {
            $resolution = (new EntityResolver())->resolveProduct($params);

            if (($resolution['status'] ?? null) !== 'resolved') {
                return $this->result(
                    'product_intelligence_snapshot',
                    $resolution['status'] ?? 'entity_resolution_failed',
                    [
                        'resolution' => $resolution,
                    ],
                    $this->meta('product_intelligence_snapshot', $role, 'read_only', false, 0),
                    [
                        [
                            'code' => 'entity_resolution_' . ($resolution['status'] ?? 'failed'),
                            'message' => 'Could not resolve the product reference.',
                        ],
                    ]
                );
            }

            $productId = (int) $resolution['id'];

            $snapshot = (new ProductIntelligenceSnapshotService())->build(
                $productId,
                $role
            );

            return $this->result(
                'product_intelligence_snapshot',
                'success',
                [
                    'product_id' => $productId,
                    'resolution' => $resolution,
                    'snapshot' => $snapshot,
                    'count' => 1,
                ],
                $this->meta('product_intelligence_snapshot', $role, 'read_only', false, 1)
            );
        } catch (Throwable $e) {
            return $this->result(
                'product_intelligence_snapshot',
                'failed',
                [],
                $this->meta('product_intelligence_snapshot', $role, 'read_only', false, 0),
                [
                    [
                        'code' => 'product_intelligence_snapshot_failed',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }
}