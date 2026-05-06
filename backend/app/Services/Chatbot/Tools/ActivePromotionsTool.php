<?php

namespace App\Services\Chatbot\Tools;

use App\Services\CatalogPromotionService;

class ActivePromotionsTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'active_promotions';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';

        $response = (new CatalogPromotionService())->activeList();
        $data = $this->extractData($response);

        $items = $data['items'] ?? (is_array($data) ? $data : []);
        $pagination = $data['pagination'] ?? null;

        $count = is_array($items) ? count($items) : 0;
        $total = $pagination['total'] ?? $count;

        return $this->result('active_promotions', $count === 0 ? 'empty' : 'success', [
            'promotions' => $items,
            'count' => $count,
            'total' => $total,
            'pagination' => $pagination,
        ], $this->meta('active_promotions', $role, 'read_only', false, $count));
    }
}