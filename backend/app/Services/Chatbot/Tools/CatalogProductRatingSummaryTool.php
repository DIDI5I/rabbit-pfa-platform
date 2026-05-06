<?php

namespace App\Services\Chatbot\Tools;

use App\Services\CatalogReviewService;

class CatalogProductRatingSummaryTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'catalog_product_rating_summary';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $productId = (int) $params['product_id'];

        $response = (new CatalogReviewService())->ratingSummary($productId);
        $data = $this->extractData($response);

        return $this->result(
            'catalog_product_rating_summary',
            empty($data) ? 'empty' : 'success',
            [
                'product_id' => $productId,
                'rating_summary' => $data,
            ],
            $this->meta('catalog_product_rating_summary', $role, 'read_only', false)
        );
    }
}