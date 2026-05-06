<?php

namespace App\Services;

use App\Repositories\PromotionProductRepository;
use App\Support\ApiResponse;

class PromotionProductService
{
    private PromotionProductRepository $repository;

    public function __construct()
    {
        $this->repository = new PromotionProductRepository();
    }

    public function list(int $promotionId): array
    {
        if (!$this->repository->promotionExists($promotionId)) {
            return ApiResponse::error('Promotion not found', [
                'promotion' => ['Promotion not found.'],
            ], 404);
        }

        return $this->repository->list($promotionId);
    }

    public function attach(int $promotionId, array $data): array
    {
        if (!$this->repository->promotionExists($promotionId)) {
            return ApiResponse::error('Promotion not found', [
                'promotion' => ['Promotion not found.'],
            ], 404);
        }

        $componentId = isset($data['component_id'])
            ? (int) $data['component_id']
            : 0;

        if ($componentId <= 0) {
            return ApiResponse::error('Validation failed', [
                'component_id' => ['component_id is required.'],
            ], 422);
        }

        if (!$this->repository->componentExists($componentId)) {
            return ApiResponse::error('Product not found', [
                'component_id' => ['Product not found or inactive.'],
            ], 404);
        }

        return $this->repository->attach($promotionId, $componentId);
    }

    public function detach(int $promotionId, int $componentId): array
    {
        if (!$this->repository->promotionExists($promotionId)) {
            return ApiResponse::error('Promotion not found', [
                'promotion' => ['Promotion not found.'],
            ], 404);
        }

        return $this->repository->detach($promotionId, $componentId);
    }
}