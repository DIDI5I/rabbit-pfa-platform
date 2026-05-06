<?php

namespace App\Controllers;

use App\Repositories\InventoryRepository;
use App\Services\InventoryService;
use App\Support\ApiResponse;

class InventoryController
{
    private InventoryService $inventoryService;

    public function __construct()
    {
        $this->inventoryService = new InventoryService(
            new InventoryRepository()
        );
    }

    public function index(): array
    {
        $items = $this->inventoryService->all();

        return ApiResponse::success(
            'Inventory fetched successfully',
            $items
        );
    }

    public function alerts(): array
    {
        $items = $this->inventoryService->alerts();

        return ApiResponse::success(
            'Inventory alerts fetched successfully',
            $items
        );
    }
}