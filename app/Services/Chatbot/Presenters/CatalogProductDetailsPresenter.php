<?php

namespace App\Services\Chatbot\Presenters;

class CatalogProductDetailsPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'catalog_product_details';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';
        $product = $toolResult['data']['product'] ?? [];

        $name = $product['name'] ?? 'this product';

        return $this->success(
            "Here are the public details for {$name}.",
            $toolResult,
            $role,
            [
                'product_id' => $product['id'] ?? null,
                'name' => $name,
                'sku' => $product['sku'] ?? null,
                'category' => $product['category'] ?? null,
                'availability_status' => $product['availability_status'] ?? null,
            ],
            [],
            [
                'has_more' => false,
                'total' => 1,
                'shown' => 1,
                'shown_this_response' => 1,
            ],
            ['Ask for related products', 'Open product page']
        );
    }
}