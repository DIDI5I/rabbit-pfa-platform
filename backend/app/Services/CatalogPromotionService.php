<?php

namespace App\Services;

use App\Repositories\CatalogPromotionRepository;
use App\Support\ApiResponse;

class CatalogPromotionService
{
    private CatalogPromotionRepository $repository;

    public function __construct()
    {
        $this->repository = new CatalogPromotionRepository();
    }

    public function activeList(): array
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;

        return $this->repository->activeList($page, $limit);
    }

    public function activeForProduct(int $productId): array
    {
        if (!$this->repository->productExists($productId)) {
            return ApiResponse::error('Product not found', [
                'product' => ['Product not found or not available in catalog.'],
            ], 404);
        }

        return $this->repository->activeForProduct($productId);
    }
}