<?php

namespace App\Controllers;

use App\Services\CatalogPromotionService;

class CatalogPromotionController
{
    private CatalogPromotionService $service;

    public function __construct()
    {
        $this->service = new CatalogPromotionService();
    }

    public function active(): array
    {
        return $this->service->activeList();
    }

    public function productPromotions(int $id): array
    {
        return $this->service->activeForProduct($id);
    }
}