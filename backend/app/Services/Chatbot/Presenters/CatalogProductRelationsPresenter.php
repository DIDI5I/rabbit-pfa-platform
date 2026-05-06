<?php

namespace App\Services\Chatbot\Presenters;

class CatalogProductRelationsPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'catalog_product_relations';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $relations = $toolResult['data']['relations'] ?? [];
        $productId = $toolResult['data']['product_id'] ?? null;
        $count = $toolResult['data']['count'] ?? count($relations);
        $shown = min(self::PREVIEW_LIMIT, count($relations));

        $answer = $count === 0
            ? "I found no public relations for product {$productId}."
            : "I found {$count} public relation(s) for product {$productId}. Showing {$shown}.";

        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'product_id' => $productId,
                'total' => $count,
                'shown' => $shown,
            ],
            $this->preview($relations),
            [
                'has_more' => $count > $shown,
                'total' => $count,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $count > $shown
                ? ['Ask to see more related products', 'Open product page']
                : ['Open product page']
        );
    }
}