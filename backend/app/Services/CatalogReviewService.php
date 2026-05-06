<?php

namespace App\Services;

use App\Forms\StoreCatalogReviewRequest;
use App\Repositories\CatalogReviewRepository;
use App\Support\ApiResponse;

class CatalogReviewService
{
    private CatalogReviewRepository $repository;

    public function __construct()
    {
        $this->repository = new CatalogReviewRepository();
    }

    public function create(int $productId, array $data): array
    {
        if (!$this->repository->productExists($productId)) {
            return ApiResponse::error('Product not found', [
                'product' => ['Product not found or not available in catalog.'],
            ], 404);
        }

        $request = new StoreCatalogReviewRequest($data);
        $request->validate();

        return $this->repository->create($productId, $request);
    }

    public function approvedList(int $productId): array
    {
        if (!$this->repository->productExists($productId)) {
            return ApiResponse::error('Product not found', [
                'product' => ['Product not found or not available in catalog.'],
            ], 404);
        }

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;

        return $this->repository->approvedList($productId, $page, $limit);
    }

    public function ratingSummary(int $productId): array
    {
        if (!$this->repository->productExists($productId)) {
            return ApiResponse::error('Product not found', [
                'product' => ['Product not found or not available in catalog.'],
            ], 404);
        }

        return $this->repository->ratingSummary($productId);
    }
}