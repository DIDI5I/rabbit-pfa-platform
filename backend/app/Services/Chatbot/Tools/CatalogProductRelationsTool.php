<?php

namespace App\Services\Chatbot\Tools;

use App\Services\CatalogProductRelationService;

class CatalogProductRelationsTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'catalog_product_relations';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $productId = (int) $params['product_id'];

        $response = (new CatalogProductRelationService())->list($productId);
        $data = $this->extractData($response);

        $items = $data['items'] ?? (is_array($data) ? $data : []);
        $count = is_array($items) ? count($items) : 0;

        return $this->result(
            'catalog_product_relations',
            $count === 0 ? 'empty' : 'success',
            [
                'product_id' => $productId,
                'relations' => $items,
                'count' => $count,
            ],
            $this->meta('catalog_product_relations', $role, 'read_only', false, $count)
        );
    }
}