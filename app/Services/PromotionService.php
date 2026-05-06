<?php

namespace App\Services;

use App\Forms\StorePromotionRequest;
use App\Forms\UpdatePromotionRequest;
use App\Repositories\PromotionRepository;
use App\Support\ApiResponse;

class PromotionService
{
    private PromotionRepository $repository;

    private array $allowedStatuses = [
        'ACTIVE',
        'UPCOMING',
        'EXPIRED',
        'DISABLED',
    ];

    public function __construct()
    {
        $this->repository = new PromotionRepository();
    }

    public function list(): array
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;

        return $this->repository->list(
            $this->filtersFromQuery(),
            $page,
            $limit
        );
    }

    public function findById(int $id): array
    {
        $promotion = $this->repository->findById($id);

        if (!$promotion) {
            return ApiResponse::error('Promotion not found', [
                'promotion' => ['Promotion not found.'],
            ], 404);
        }

        return ApiResponse::success('Promotion fetched successfully', $promotion);
    }

    public function create(array $data): array
    {
        $request = new StorePromotionRequest($data);
        $request->validate();

        $promotion = $this->repository->create($request);

        return ApiResponse::success('Promotion created successfully', $promotion, 201);
    }

    public function update(int $id, array $data): array
    {
        if (!$this->repository->existsById($id)) {
            return ApiResponse::error('Promotion not found', [
                'promotion' => ['Promotion not found.'],
            ], 404);
        }

        $request = new UpdatePromotionRequest($data);
        $request->validate();

        $promotion = $this->repository->update($id, $request);

        return ApiResponse::success('Promotion updated successfully', $promotion);
    }

    public function delete(int $id): array
    {
        if (!$this->repository->existsById($id)) {
            return ApiResponse::error('Promotion not found', [
                'promotion' => ['Promotion not found.'],
            ], 404);
        }

        $this->repository->delete($id);

        return ApiResponse::success('Promotion deleted successfully', [
            'id' => $id,
        ]);
    }

    private function filtersFromQuery(): array
    {
        $status = isset($_GET['status'])
            ? strtoupper(trim((string) $_GET['status']))
            : null;

        $search = isset($_GET['search'])
            ? trim((string) $_GET['search'])
            : null;

        return [
            'status' => $this->validStatus($status),
            'search' => $search !== '' ? $search : null,
            'active_only' => null,
        ];
    }

    private function validStatus(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        return in_array($status, $this->allowedStatuses, true)
            ? $status
            : null;
    }
}