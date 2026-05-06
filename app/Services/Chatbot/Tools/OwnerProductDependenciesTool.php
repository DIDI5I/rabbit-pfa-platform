<?php

namespace App\Services\Chatbot\Tools;

use App\Services\DependencyService;

class OwnerProductDependenciesTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'owner_product_dependencies';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
       $resolution = (new \App\Services\Chatbot\EntityResolver())->resolveProduct($params);

        if ($resolution['status'] !== 'resolved') {
            return $this->result(
                'owner_product_dependencies',
                $resolution['status'],
                [
                    'resolution' => $resolution,
                ],
                $this->meta('owner_product_dependencies', $role, 'read_only', true, 0),
                [
                    [
                        'code' => 'entity_resolution_' . $resolution['status'],
                        'message' => 'Could not resolve the product reference.',
                    ],
                ]
            );
        }

        $productId = (int) $resolution['id'];
        $response = (new DependencyService())->listForProduct($productId);
        $data = $this->extractData($response);

        $items = $data['items'] ?? (is_array($data) ? $data : []);
        $count = is_array($items) ? count($items) : 0;

        return $this->result(
            'owner_product_dependencies',
            $count === 0 ? 'empty' : 'success',
            [
                'product_id' => $productId,
                'dependencies' => $items,
                'count' => $count,
                'resolution' => $resolution,
                'resolved_item' => $resolution['resolved_item'] ?? null,
            ],
            $this->meta('owner_product_dependencies', $role, 'read_only', true, $count)
        );
    }
}