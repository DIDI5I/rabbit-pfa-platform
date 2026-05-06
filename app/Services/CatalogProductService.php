<?php

namespace App\Services;

use App\Repositories\CatalogProductRepository;
use App\Support\ApiResponse;

class CatalogProductService
{
    private CatalogProductRepository $repository;

    private array $allowedCategories = [
        'assembly',
        'sub_assembly',
        'component',
        'raw_material',
    ];

    private array $allowedAvailability = [
        'AVAILABLE',
        'LOW_AVAILABILITY',
        'OUT_OF_STOCK',
    ];

    public function __construct()
    {
        $this->repository = new CatalogProductRepository();
    }

    public function list(array $filters = []): array
    {
        $normalizedFilters = [
            'search' => $filters['search'] ?? null,
            'category' => $filters['category'] ?? null,
            'availability' => $filters['availability'] ?? null,
        ];

        $page = isset($filters['page'])
            ? max(1, (int) $filters['page'])
            : 1;

        $limit = isset($filters['limit'])
            ? max(1, min(50, (int) $filters['limit']))
            : 20;

        return $this->repository->list($normalizedFilters, $page, $limit);
    }

    public function findById(int $id): array
    {
        $product = $this->repository->findById($id);

        if (!$product) {
            return ApiResponse::error('Product not found', [
                'product' => ['Product not found or not available in catalog.'],
            ], 404);
        }

        return ApiResponse::success('Catalog product fetched successfully', $product);
    }

    private function filtersFromQuery(): array
    {
        $search = isset($_GET['search'])
            ? trim((string) $_GET['search'])
            : null;

        $category = isset($_GET['category'])
            ? trim((string) $_GET['category'])
            : null;

        $availability = isset($_GET['availability'])
            ? strtoupper(trim((string) $_GET['availability']))
            : null;

        return [
            'search' => $search !== '' ? $search : null,
            'category' => $this->validCategory($category),
            'availability' => $this->validAvailability($availability),
        ];
    }

    private function validCategory(?string $category): ?string
    {
        if ($category === null || $category === '') {
            return null;
        }

        return in_array($category, $this->allowedCategories, true)
            ? $category
            : null;
    }

    private function validAvailability(?string $availability): ?string
    {
        if ($availability === null || $availability === '') {
            return null;
        }

        return in_array($availability, $this->allowedAvailability, true)
            ? $availability
            : null;
    }
}