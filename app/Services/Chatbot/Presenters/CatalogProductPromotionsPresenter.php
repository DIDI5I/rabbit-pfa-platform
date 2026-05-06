<?php

namespace App\Services\Chatbot\Presenters;

class CatalogProductPromotionsPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'catalog_product_promotions';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $promotions = $toolResult['data']['promotions'] ?? [];
        $productId = $toolResult['data']['product_id'] ?? null;
        $count = $toolResult['data']['count'] ?? count($promotions);
        $shown = min(self::PREVIEW_LIMIT, count($promotions));

        $answer = $count === 0
            ? "Product {$productId} has no active promotion right now."
            : "Product {$productId} has {$count} active promotion(s).";

        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'product_id' => $productId,
                'total' => $count,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $this->preview($promotions, [
                'id',
                'title',
                'discount_type',
                'discount_value',
                'starts_at',
                'ends_at',
                'status',
            ]),
            [
                'has_more' => $count > $shown,
                'total' => $count,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            ['Open product page', 'Open promotions page']
        );
    }
}