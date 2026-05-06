<?php

namespace App\Services\Chatbot\Tools;

use App\Services\DashboardStockService;

class DashboardStockSummaryTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'dashboard_stock_summary';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';

        $response = (new DashboardStockService())->general();
        $data = $this->extractData($response);

        return $this->result('dashboard_stock_summary', empty($data) ? 'empty' : 'success', [
            'summary' => $data ?? [],
        ], $this->meta('dashboard_stock_summary', $role, 'read_only', true));
    }
}