<?php

namespace App\Services\Chatbot\Tools\StockIntelligenceDashboard;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\StockIntelligenceService;
use Throwable;

class StockIntelligenceDashboardExplanationTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'stock_intelligence_dashboard_explanation';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';

        try {
            $response = (new StockIntelligenceService())->reorderRecommendations();

            $data = $response['data'] ?? [];
            $items = is_array($data['items'] ?? null) ? $data['items'] : [];
            $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];

            $recommended = array_values(array_filter($items, function (array $item) {
                return (bool) ($item['recommendation'] ?? false);
            }));

            $critical = array_values(array_filter($recommended, function (array $item) {
                return strtoupper((string) ($item['priority'] ?? '')) === 'CRITICAL';
            }));

            $high = array_values(array_filter($recommended, function (array $item) {
                return strtoupper((string) ($item['priority'] ?? '')) === 'HIGH';
            }));

            return $this->result(
                'stock_intelligence_dashboard_explanation',
                'success',
                [
                    'summary' => $summary,
                    'recommended_total' => count($recommended),
                    'critical_preview' => array_slice($critical, 0, 5),
                    'high_preview' => array_slice($high, 0, 5),
                    'engine' => [
                        'period_days' => $data['period_days'] ?? null,
                        'forecast_days' => $data['forecast_days'] ?? null,
                        'calculation_mode' => $data['calculation_mode'] ?? null,
                        'filters' => $data['filters'] ?? [],
                    ],
                    'count' => 1,
                ],
                $this->meta('stock_intelligence_dashboard_explanation', $role, 'read_only', true, 1)
            );
        } catch (Throwable $e) {
            return $this->result(
                'stock_intelligence_dashboard_explanation',
                'failed',
                [],
                $this->meta('stock_intelligence_dashboard_explanation', $role, 'read_only', true, 0),
                [
                    [
                        'code' => 'stock_intelligence_dashboard_explanation_failed',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }
}