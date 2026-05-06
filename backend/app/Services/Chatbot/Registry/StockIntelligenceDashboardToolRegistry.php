<?php

namespace App\Services\Chatbot\Registry;

class StockIntelligenceDashboardToolRegistry
{
    public function tools(): array
    {
        return [
            'stock_intelligence_summary' => [
                'roles' => ['owner'],
                'tool' => 'stock_intelligence_summary',
                'operation_type' => 'read_only',
                'sensitive' => true,
                'required_params' => [],
            ],

            'stock_intelligence_dashboard_explanation' => [
                'roles' => ['owner'],
                'tool' => 'stock_intelligence_dashboard_explanation',
                'operation_type' => 'read_only',
                'sensitive' => true,
                'required_params' => [],
            ],
        ];
    }
}