<?php

namespace App\Services\Chatbot\Tools;

use App\Services\StockIntelligenceService;

class ReorderRecommendationsTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'reorder_recommendations';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';

        $periodDays = isset($params['period_days']) ? (int) $params['period_days'] : 90;
        $forecastDays = isset($params['forecast_days']) ? (int) $params['forecast_days'] : 30;

        $filters = [
            'only_recommended' => $params['only_recommended'] ?? null,
            'priority' => $params['priority'] ?? null,
            'confidence' => $params['confidence'] ?? null,
            'model' => $params['model'] ?? null,
            'include_diagnostics' => $params['include_diagnostics'] ?? null,
        ];

        $response = (new StockIntelligenceService())->reorderRecommendations(
            $periodDays,
            $forecastDays,
            $filters
        );

        $data = $this->extractData($response);

        $recommendations = $data['recommendations'] ?? $data ?? [];

        $count = is_array($recommendations)
            ? count($recommendations)
            : 0;

        return $this->result('reorder_recommendations', $count === 0 ? 'empty' : 'success', [
            'recommendations' => $recommendations,
            'count' => $count,
            'summary' => $data['summary'] ?? [],
            'parameters' => [
                'period_days' => $periodDays,
                'forecast_days' => $forecastDays,
                'filters' => $filters,
            ],
        ], $this->meta('reorder_recommendations', $role, 'read_only', true, $count));
    }
}