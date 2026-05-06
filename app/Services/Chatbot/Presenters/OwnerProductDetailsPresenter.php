<?php

namespace App\Services\Chatbot\Presenters;

class OwnerProductDetailsPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'owner_product_details';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $product = $toolResult['data']['product'] ?? [];
        $productId = $toolResult['data']['product_id'] ?? ($product['id'] ?? null);

        $name = $product['name'] ?? "product {$productId}";

        return $this->success(
            "Here are the internal details for {$name}.",
            $toolResult,
            $role,
            [
                'product_id' => $productId,
                'name' => $product['name'] ?? null,
                'sku' => $product['sku'] ?? null,
                'category' => $product['category'] ?? null,
                'is_active' => $product['is_active'] ?? null,
                'availability_status' => $product['availability_status'] ?? null,
            ],
            [],
            [
                'has_more' => false,
                'total' => 1,
                'shown' => 1,
                'shown_this_response' => 1,
            ],
            [
                'Ask for dependencies',
                'Ask for cost rollup',
                'Open owner product page',
            ]
        );
    }
}