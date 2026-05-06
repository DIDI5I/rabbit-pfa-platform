<?php

namespace App\Controllers;

use App\Services\PromotionProductService;

class PromotionProductController
{
    private PromotionProductService $service;

    public function __construct()
    {
        $this->service = new PromotionProductService();
    }

    public function index(int $id): array
    {
        return $this->service->list($id);
    }

    public function store(int $id): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        return $this->service->attach($id, $data);
    }

    public function destroy(int $id, int $componentId): array
    {
        return $this->service->detach($id, $componentId);
    }
}