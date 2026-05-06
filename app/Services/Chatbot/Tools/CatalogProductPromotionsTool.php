<?php

namespace App\Services\Chatbot\Tools;

use App\Services\CatalogPromotionService;

class CatalogProductPromotionsTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'catalog_product_promotions';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $productId = (int) $params['product_id'];

        $response = (new CatalogPromotionService())->activeForProduct($productId);
        $data = $this->extractData($response);

        $items = $data['items'] ?? (is_array($data) ? $data : []);
        $count = is_array($items) ? count($items) : 0;

        return $this->result(
            'catalog_product_promotions',
            $count === 0 ? 'empty' : 'success',
            [
                'product_id' => $productId,
                'promotions' => $items,
                'count' => $count,
            ],
            $this->meta('catalog_product_promotions', $role, 'read_only', false, $count)
        );
    }
}