<?php

namespace App\Services\Chatbot;

use App\Services\CatalogProductService;

class EntityResolver
{
    public function resolveProduct(array $params): array
    {
        if (!empty($params['product_id'])) {
            $productId = (int) $params['product_id'];

            $response = (new CatalogProductService())->findById($productId);
            $data = $response['data'] ?? $response;

            return [
                'status' => 'resolved',
                'entity_type' => 'product',
                'id' => $productId,
                'match_type' => 'id',
                'matches' => [],
                'resolved_item' => is_array($data) ? $data : null,
                'original_params' => $params,
            ];
        }

        $query = $params['product_ref']
            ?? $params['search']
            ?? $params['name']
            ?? $params['sku']
            ?? null;

        if (!$query) {
            return [
                'status' => 'missing_reference',
                'entity_type' => 'product',
                'id' => null,
                'match_type' => null,
                'matches' => [],
            ];
        }

        return $this->resolveCatalogEntity($query, 'product', $params);
    }

    public function resolveComponent(array $params): array
    {
        if (!empty($params['component_id'])) {
            $componentId = (int) $params['component_id'];

            $response = (new CatalogProductService())->findById($componentId);
            $data = $response['data'] ?? $response;

            return [
                'status' => 'resolved',
                'entity_type' => 'component',
                'id' => $componentId,
                'match_type' => 'id',
                'matches' => [],
                'resolved_item' => is_array($data) ? $data : null,
                'original_params' => $params,
            ];
        }

        $query = $params['component_ref']
            ?? $params['search']
            ?? $params['name']
            ?? $params['sku']
            ?? null;

        if (!$query) {
            return [
                'status' => 'missing_reference',
                'entity_type' => 'component',
                'id' => null,
                'match_type' => null,
                'matches' => [],
            ];
        }

        return $this->resolveCatalogEntity($query, 'component', $params);
    }

    private function resolveCatalogEntity(string $query, string $entityType, array $originalParams = []): array
    {
        $query = trim($query);

        if ($query === '') {
            return [
                'status' => 'missing_reference',
                'entity_type' => $entityType,
                'id' => null,
                'match_type' => null,
                'matches' => [],
                'original_params' => $originalParams,
            ];
        }

        $response = (new CatalogProductService())->list([
            'search' => $query,
            'page' => 1,
            'limit' => 10,
        ]);

        $data = $response['data'] ?? $response;
        $items = $data['items'] ?? [];

        if (!is_array($items) || count($items) === 0) {
            return [
                'status' => 'not_found',
                'entity_type' => $entityType,
                'id' => null,
                'match_type' => null,
                'query' => $query,
                'matches' => [],
                'original_params' => $originalParams,
            ];
        }

        $exactSkuMatches = array_values(array_filter($items, function (array $item) use ($query) {
            return strtolower($item['sku'] ?? '') === strtolower($query);
        }));

        if (count($exactSkuMatches) === 1) {
            return [
                'status' => 'resolved',
                'entity_type' => $entityType,
                'id' => (int) $exactSkuMatches[0]['id'],
                'match_type' => 'exact_sku',
                'query' => $query,
                'matches' => [],
                'resolved_item' => $exactSkuMatches[0],
                'original_params' => $originalParams,
            ];
        }

        if (count($items) === 1) {
            return [
                'status' => 'resolved',
                'entity_type' => $entityType,
                'id' => (int) $items[0]['id'],
                'match_type' => 'single_search_result',
                'query' => $query,
                'matches' => [],
                'resolved_item' => $items[0],
                'original_params' => $originalParams,
            ];
        }

        return [
            'status' => 'needs_clarification',
            'entity_type' => $entityType,
            'id' => null,
            'match_type' => 'multiple_matches',
            'query' => $query,
            'original_params' => $originalParams,
            'matches' => array_slice(array_map(function (array $item) {
                return [
                    'id' => $item['id'] ?? null,
                    'name' => $item['name'] ?? null,
                    'sku' => $item['sku'] ?? null,
                    'category' => $item['category'] ?? null,
                    'availability_status' => $item['availability_status'] ?? null,
                ];
            }, $items), 0, 5),
        ];
    }
}