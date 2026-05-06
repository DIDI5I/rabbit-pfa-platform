<?php

namespace App\Services\Chatbot\Tools;

use App\Services\StockService;

class StockMovementsByComponentTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'stock_movements_by_component';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $resolution = (new \App\Services\Chatbot\EntityResolver())->resolveComponent($params);

        if ($resolution['status'] !== 'resolved') {
            return $this->result(
                'stock_movements_by_component',
                $resolution['status'],
                [
                    'resolution' => $resolution,
                ],
                $this->meta('stock_movements_by_component', $role, 'read_only', true, 0),
                [
                    [
                        'code' => 'entity_resolution_' . $resolution['status'],
                        'message' => 'Could not resolve the component reference.',
                    ],
                ]
            );
        }

        $componentId = (int) $resolution['id'];

        $response = (new StockService())->getMovements($componentId);
        $data = $this->extractData($response);

        $items = $data['items'] ?? (is_array($data) ? $data : []);
        $count = is_array($items) ? count($items) : 0;

        return $this->result(
            'stock_movements_by_component',
            $count === 0 ? 'empty' : 'success',
            [
                'component_id' => $componentId,
                'stock_movements' => $items,
                'count' => $count,
                'resolution' => $resolution,
            ],
            $this->meta('stock_movements_by_component', $role, 'read_only', true, $count)
        );
    }
}