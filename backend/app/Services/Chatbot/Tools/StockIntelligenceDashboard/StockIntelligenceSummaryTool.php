<?php

namespace App\Services\Chatbot\Tools\StockIntelligenceDashboard;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\StockIntelligenceService;
use Throwable;

class StockIntelligenceSummaryTool extends BaseToolHandler
{
    private const RECOMMENDED_PREVIEW_LIMIT = 5;

    public function supports(string $intent): bool
    {
        return $intent === 'stock_intelligence_summary';
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

            usort($recommended, function (array $a, array $b) {
                return $this->priorityRank($b['priority'] ?? 'NONE')
                    <=> $this->priorityRank($a['priority'] ?? 'NONE');
            });

            $preview = array_slice($recommended, 0, self::RECOMMENDED_PREVIEW_LIMIT);

            return $this->result(
                'stock_intelligence_summary',
                'success',
                [
                    'summary' => $summary,
                    'recommended_preview' => $preview,
                    'recommended_total' => count($recommended),
                    'engine' => [
                        'period_days' => $data['period_days'] ?? null,
                        'forecast_days' => $data['forecast_days'] ?? null,
                        'calculation_mode' => $data['calculation_mode'] ?? null,
                        'filters' => $data['filters'] ?? [],
                    ],
                    'count' => count($recommended),
                ],
                $this->meta('stock_intelligence_summary', $role, 'read_only', true, count($recommended))
            );
        } catch (Throwable $e) {
            return $this->result(
                'stock_intelligence_summary',
                'failed',
                [],
                $this->meta('stock_intelligence_summary', $role, 'read_only', true, 0),
                [
                    [
                        'code' => 'stock_intelligence_summary_failed',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }

    private function priorityRank(string $priority): int
    {
        return match (strtoupper($priority)) {
            'CRITICAL' => 4,
            'HIGH' => 3,
            'MEDIUM' => 2,
            'LOW' => 1,
            default => 0,
        };
    }
}