<?php

namespace App\Services;

use App\Repositories\CatalogProductRelationRepository;
use App\Support\ApiResponse;

class CatalogProductRelationService
{
    private CatalogProductRelationRepository $repository;

    public function __construct()
    {
        $this->repository = new CatalogProductRelationRepository();
    }

    public function list(int $productId): array
    {
        if (!$this->repository->productExists($productId)) {
            return ApiResponse::error('Product not found', [
                'product' => ['Product not found or not available in catalog.'],
            ], 404);
        }

        return $this->repository->list($productId);
    }
}