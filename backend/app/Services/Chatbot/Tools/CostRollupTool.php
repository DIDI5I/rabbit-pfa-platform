<?php

namespace App\Services\Chatbot\Tools;

use App\Services\CostRollupService;

class CostRollupTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'cost_rollup';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';

        $response = (new CostRollupService())->calculate((int) $params['product_id']);
        $data = $this->extractData($response);

        return $this->result('cost_rollup', empty($data) ? 'empty' : 'success', $data, [
            'intent' => 'cost_rollup',
            'operation_type' => 'read_only',
            'role_scope' => $role,
            'sensitive' => true,
            'count' => 1,
        ]);
    }
}