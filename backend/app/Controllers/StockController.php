<?php

namespace App\Controllers;

use App\Services\StockService;
use App\Forms\StoreStockMovementRequest;

class StockController
{
    public function show(int $componentId): array
    {
        $service = new StockService();

        return $service->getStock($componentId);
    }

    public function movements(int $componentId): array
    {
        $service = new StockService();

        return $service->getMovements($componentId);
    }

    public function storeMovement(): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $request = new StoreStockMovementRequest($data);
        $request->validate();

        $service = new StockService();

        /*
         * Later, replace null with the authenticated user id:
         * Auth::id()
         */
        return $service->recordMovement($request, null);
    }
}