<?php

namespace App\Services;

use App\Repositories\StockIntelligenceRepository;
use App\Support\ApiResponse;
use App\Services\StockIntelligence\StockIntelligenceSummaryBuilder;
use App\Services\StockIntelligence\StockIntelligenceFilter;
use App\Services\StockIntelligence\StockIntelligenceDiagnostics;
use App\Services\StockIntelligence\StockIntelligenceOutlierAnalyzer;
use App\Services\StockIntelligence\StockIntelligenceItemBuilder;

class StockIntelligenceService
{
    protected StockIntelligenceRepository $repository;
    private StockIntelligenceFilter $filter;
    private StockIntelligenceDiagnostics $diagnostics;
    private StockIntelligenceOutlierAnalyzer $outlierAnalyzer;
    private StockIntelligenceItemBuilder $itemBuilder;

    public function __construct()
    {
        $this->repository = new StockIntelligenceRepository();
        $this->summaryBuilder = new StockIntelligenceSummaryBuilder();
        $this->filter = new StockIntelligenceFilter();
        $this->diagnostics = new StockIntelligenceDiagnostics();
        $this->outlierAnalyzer = new StockIntelligenceOutlierAnalyzer();
        $this->itemBuilder = new StockIntelligenceItemBuilder(
            $this->outlierAnalyzer
        );
    }

   public function reorderRecommendations(int $periodDays = 90, int $forecastDays = 30, array $filters = []): array{
        $periodDays = $this->normalizeDays($periodDays, 30, 730);
        $forecastDays = $this->normalizeDays($forecastDays, 7, 365);

        $products = $this->repository->baseProductRows();
        $outflows = $this->repository->outflowSummary($periodDays);
        $histories = $this->repository->outflowHistory($periodDays);
        $items = [];

        foreach ($products as $product) {
            $productId = (int) $product['product_id'];

            $items[] = $this->itemBuilder->build(
                $product,
                $outflows[$productId] ?? null,
                $histories[$productId] ?? [],
                $periodDays,
                $forecastDays
            );
        }

        $items = $this->filter->apply($items, $filters);

        $normalizedFilters = $this->filter->normalize($filters);
        if ($normalizedFilters['include_diagnostics'] !== true) {
            $items = $this->diagnostics->strip($items);
        }

        return ApiResponse::success('Reorder recommendations fetched successfully', [
            'period_days' => $periodDays,
            'forecast_days' => $forecastDays,
            'calculation_mode' => 'stock_intelligence_v1',
            'filters' => $normalizedFilters,
            'items' => array_values($items),
            'summary' => $this->summaryBuilder->build($items),
        ]);
    }

   
    private function normalizeDays(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }


}