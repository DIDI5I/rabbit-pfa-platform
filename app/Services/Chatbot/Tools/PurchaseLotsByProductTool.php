<?php

namespace App\Services\Chatbot\Tools;

use App\Services\PurchaseLotService;

class PurchaseLotsByProductTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'purchase_lots_by_product';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $resolution = (new \App\Services\Chatbot\EntityResolver())->resolveProduct($params);

        if ($resolution['status'] !== 'resolved') {
            return $this->result(
                'purchase_lots_by_product',
                $resolution['status'],
                [
                    'resolution' => $resolution,
                ],
                $this->meta('purchase_lots_by_product', $role, 'read_only', true, 0),
                [
                    [
                        'code' => 'entity_resolution_' . $resolution['status'],
                        'message' => 'Could not resolve the product reference.',
                    ],
                ]
            );
        }

        $productId = (int) $resolution['id'];

        $response = (new PurchaseLotService())->byProduct($productId);
        $data = $this->extractData($response);

        $items = $data['items'] ?? (is_array($data) ? $data : []);
        $count = is_array($items) ? count($items) : 0;

        return $this->result(
            'purchase_lots_by_product',
            $count === 0 ? 'empty' : 'success',
            [
                'product_id' => $productId,
                'purchase_lots' => $items,
                'count' => $count,
                'resolution' => $resolution,
            ],
            $this->meta('purchase_lots_by_product', $role, 'read_only', true, $count)
        );
    }
}