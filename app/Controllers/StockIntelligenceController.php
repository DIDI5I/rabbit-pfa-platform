<?php

namespace App\Controllers;

use App\Services\StockIntelligenceService;

class StockIntelligenceController
{
    protected StockIntelligenceService $service;

    public function __construct()
    {
        $this->service = new StockIntelligenceService();
    }

    public function reorderRecommendations(): array
    {
        $periodDays = isset($_GET['period_days'])
            ? (int) $_GET['period_days']
            : 90;

        $forecastDays = isset($_GET['forecast_days'])
            ? (int) $_GET['forecast_days']
            : 30;

        $filters = [
            'only_recommended' => $_GET['only_recommended'] ?? null,
            'priority' => $_GET['priority'] ?? null,
            'confidence' => $_GET['confidence'] ?? null,
            'model' => $_GET['model'] ?? null,
            'include_diagnostics' => $_GET['include_diagnostics'] ?? null,
        ];

        return $this->service->reorderRecommendations(
            $periodDays,
            $forecastDays,
            $filters
        );
    }
}