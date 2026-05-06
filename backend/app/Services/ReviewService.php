<?php

namespace App\Services;

use App\Forms\UpdateReviewStatusRequest;
use App\Repositories\ReviewRepository;
use App\Support\ApiResponse;

class ReviewService
{
    private ReviewRepository $repository;

    private array $allowedStatuses = [
        'pending',
        'approved',
        'rejected',
    ];

    public function __construct()
    {
        $this->repository = new ReviewRepository();
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
        $review = $this->repository->findById($id);

        if (!$review) {
            return ApiResponse::error('Review not found', [
                'review' => ['Review not found.'],
            ], 404);
        }

        return ApiResponse::success('Review fetched successfully', $review);
    }

    public function updateStatus(int $id, array $data): array
    {
        if (!$this->repository->existsById($id)) {
            return ApiResponse::error('Review not found', [
                'review' => ['Review not found.'],
            ], 404);
        }

        $request = new UpdateReviewStatusRequest($data);
        $request->validate();

        $review = $this->repository->updateStatus($id, $request->status());

        return ApiResponse::success('Review status updated successfully', $review);
    }

    public function delete(int $id): array
    {
        if (!$this->repository->existsById($id)) {
            return ApiResponse::error('Review not found', [
                'review' => ['Review not found.'],
            ], 404);
        }

        $this->repository->delete($id);

        return ApiResponse::success('Review deleted successfully', [
            'id' => $id,
        ]);
    }

    private function filtersFromQuery(): array
    {
        $status = isset($_GET['status'])
            ? strtolower(trim((string) $_GET['status']))
            : null;

        $search = isset($_GET['search'])
            ? trim((string) $_GET['search'])
            : null;

        $componentId = isset($_GET['component_id'])
            ? (int) $_GET['component_id']
            : null;

        $userId = isset($_GET['user_id'])
            ? (int) $_GET['user_id']
            : null;

        $rating = isset($_GET['rating'])
            ? (int) $_GET['rating']
            : null;

        return [
            'status' => $this->validStatus($status),
            'search' => $search !== '' ? $search : null,
            'component_id' => $componentId > 0 ? $componentId : null,
            'user_id' => $userId > 0 ? $userId : null,
            'rating' => $rating >= 1 && $rating <= 5 ? $rating : null,
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