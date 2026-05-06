<?php

namespace App\Services\Chatbot\Presenters;

class CatalogProductReviewsPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'catalog_product_reviews';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $reviews = $toolResult['data']['reviews'] ?? [];
        $productId = $toolResult['data']['product_id'] ?? null;
        $count = $toolResult['data']['count'] ?? count($reviews);
        $total = $toolResult['data']['total'] ?? $count;
        $pagination = $toolResult['data']['pagination'] ?? null;

        $shown = min(self::PREVIEW_LIMIT, count($reviews));

        if ($count === 0) {
            $answer = "I found no approved reviews for product {$productId}.";
        } elseif ($total > $shown) {
            $answer = "I found {$total} approved review(s) for product {$productId}. Showing the first {$shown}.";
        } else {
            $answer = "I found {$total} approved review(s) for product {$productId}.";
        }

        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'product_id' => $productId,
                'total' => $total,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $this->preview($reviews),
            $this->listMeta($total, $shown, $pagination, [
                'product_id' => $productId,
                'status' => 'approved',
            ]),
            $total > $shown
                ? ['Ask to see more reviews', 'Open product page']
                : ['Open product page']
        );
    }
}