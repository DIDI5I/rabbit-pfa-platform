<?php

namespace App\Services\Chatbot\Tools;

use App\Services\ProductService;

class OwnerProductDetailsTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'owner_product_details';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $resolution = (new \App\Services\Chatbot\EntityResolver())->resolveProduct($params);

        if ($resolution['status'] !== 'resolved') {
            return $this->result(
                'owner_product_details',
                $resolution['status'],
                [
                    'resolution' => $resolution,
                ],
                $this->meta('owner_product_details', $role, 'read_only', true, 0),
                [
                    [
                        'code' => 'entity_resolution_' . $resolution['status'],
                        'message' => 'Could not resolve the product reference.',
                    ],
                ]
            );
        }

        $productId = (int) $resolution['id'];

        $response = (new ProductService())->show($productId);
        $data = $this->extractData($response);

        if (empty($data)) {
            return $this->result(
                'owner_product_details',
                'not_found',
                ['product_id' => $productId],
                $this->meta('owner_product_details', $role, 'read_only', true, 0),
                [
                    [
                        'code' => 'product_not_found',
                        'message' => 'Product was not found.',
                    ],
                ]
            );
        }

        return $this->result(
            'owner_product_details',
            'success',
            [
                'product_id' => $productId,
                'product' => $data,
                'resolution' => $resolution,
            ],
            $this->meta('owner_product_details', $role, 'read_only', true, 1)
        );
    }
}