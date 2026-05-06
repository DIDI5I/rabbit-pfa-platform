<?php

namespace App\Controllers;

use App\Services\DashboardStockService;

class DashboardStockController
{
    public function general(): array
    {
        $service = new DashboardStockService();

        return $service->general();
    }

    public function productsPerformance(): array
    {
        $periodDays = isset($_GET['period_days'])
            ? (int) $_GET['period_days']
            : 360;

        $service = new DashboardStockService();

        return $service->productsPerformance($periodDays);
    }

    public function productPerformance(int $productId): array
    {
        $periodDays = isset($_GET['period_days'])
            ? (int) $_GET['period_days']
            : 360;

        $service = new DashboardStockService();

        return $service->productDashboard($productId, $periodDays);
    }

    public function abc(): array
    {
        $service = new DashboardStockService();

        return $service->abc();
    }

    public function abcClass(string $class): array
    {
        $service = new DashboardStockService();

        return $service->abcClass($class);
    }
}