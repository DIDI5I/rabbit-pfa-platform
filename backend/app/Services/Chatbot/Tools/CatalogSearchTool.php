<?php

namespace App\Services\Chatbot\Tools;

use App\Services\CatalogProductService;

class CatalogSearchTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'catalog_search';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';

        $filters = [
            'search' => $params['search'] ?? null,
            'category' => $params['category'] ?? null,
            'availability' => $params['availability'] ?? null,
            'page' => $params['page'] ?? 1,
            'limit' => $params['limit'] ?? 5,
        ];

        $response = (new CatalogProductService())->list($filters);
        $data = $this->extractData($response);

        $items = $data['items'] ?? (is_array($data) ? $data : []);
        $pagination = $data['pagination'] ?? null;

        $count = is_array($items) ? count($items) : 0;
        $total = $pagination['total'] ?? $count;

        return $this->result('catalog_search', $count === 0 ? 'empty' : 'success', [
            'products' => $items,
            'count' => $count,
            'total' => $total,
            'pagination' => $pagination,
            'filters' => $data['filters'] ?? $filters,
        ], $this->meta('catalog_search', $role, 'read_only', false, $count));
    }
}