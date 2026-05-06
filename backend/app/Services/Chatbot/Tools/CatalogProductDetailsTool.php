<?php

namespace App\Services\Chatbot\Tools;

use App\Services\CatalogProductService;

class CatalogProductDetailsTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'catalog_product_details';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $productId = (int) $params['product_id'];

        $response = (new CatalogProductService())->findById($productId);
        $data = $this->extractData($response);

        if (!$data) {
            return $this->result('catalog_product_details', 'not_found', [
                'product_id' => $productId,
            ], $this->meta('catalog_product_details', $role, 'read_only', false, 0), [
                [
                    'code' => 'product_not_found',
                    'message' => 'Product was not found.',
                ],
            ]);
        }

        return $this->result('catalog_product_details', 'success', [
            'product' => $data,
        ], $this->meta('catalog_product_details', $role, 'read_only', false, 1));
    }
}