<?php

namespace App\Services\Chatbot\Presenters;

class CatalogProductRatingSummaryPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'catalog_product_rating_summary';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $productId = $toolResult['data']['product_id'] ?? null;
        $summary = $toolResult['data']['rating_summary'] ?? [];

        $average = $summary['average_rating']
            ?? $summary['rating_average']
            ?? $summary['avg_rating']
            ?? null;

        $count = $summary['review_count']
            ?? $summary['total_reviews']
            ?? $summary['count']
            ?? null;

        if ($average === null && $count === null) {
            $answer = "I could not find a rating summary for product {$productId}.";
        } else {
            $answer = "Product {$productId} has an average rating of {$average} based on {$count} review(s).";
        }

        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'product_id' => $productId,
                'average_rating' => $average,
                'review_count' => $count,
                'raw_summary' => $summary,
            ],
            [],
            [
                'has_more' => false,
                'total' => 1,
                'shown' => 1,
                'shown_this_response' => 1,
            ],
            ['Open product page', 'Ask to see reviews']
        );
    }
}