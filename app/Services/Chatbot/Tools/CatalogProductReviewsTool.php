<?php

namespace App\Services\Chatbot\Tools;

use App\Services\CatalogReviewService;

class CatalogProductReviewsTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'catalog_product_reviews';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $productId = (int) $params['product_id'];

        $response = (new CatalogReviewService())->approvedList($productId);
        $data = $this->extractData($response);

        $items = $data['items'] ?? (is_array($data) ? $data : []);
        $pagination = $data['pagination'] ?? null;

        $count = is_array($items) ? count($items) : 0;
        $total = $pagination['total'] ?? $count;

        return $this->result(
            'catalog_product_reviews',
            $count === 0 ? 'empty' : 'success',
            [
                'product_id' => $productId,
                'reviews' => $items,
                'count' => $count,
                'total' => $total,
                'pagination' => $pagination,
            ],
            $this->meta('catalog_product_reviews', $role, 'read_only', false, $count)
        );
    }
}