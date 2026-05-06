<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use App\Repositories\DependencyRepository;
use App\Forms\StoreDependencyRequest;
use App\Forms\UpdateDependencyRequest;
use App\Exceptions\ValidationException;
use App\Support\ApiResponse;

class DependencyService
{
    protected DependencyRepository $repository;
    protected ProductRepository $products;

    public function __construct()
    {
        $this->repository = new DependencyRepository();
        $this->products = new ProductRepository();
    }

    public function listForProduct(int $productId): array
    {
        if (!$this->products->findById($productId)) {
            throw new ValidationException([
                'product' => ['Product not found']
            ]);
        }

        return ApiResponse::success(
            'Product relationships fetched successfully',
            $this->repository->findChildren($productId)
        );
    }

    public function addChild(int $parentId, StoreDependencyRequest $request): array
    {
        $childId = $request->childId();
        $relationType = $request->relationType();

        if (!$this->products->findById($parentId)) {
            throw new ValidationException([
                'parent_id' => ['Parent product not found']
            ]);
        }

        if (!$this->products->findById($childId)) {
            throw new ValidationException([
                'child_id' => ['Child product not found']
            ]);
        }

        if ($parentId === $childId) {
            throw new ValidationException([
                'relationship' => ['A product cannot be related to itself']
            ]);
        }

        if ($this->repository->relationshipExists($parentId, $childId, $relationType)) {
            throw new ValidationException([
                'relationship' => ['Product relationship already exists']
            ]);
        }

        if ($this->repository->wouldCreateCycle($parentId, $childId)) {
            throw new ValidationException([
                'relationship' => ['Circular product relationship detected']
            ]);
        }

        $this->repository->create(
            $parentId,
            $childId,
            $relationType,
            $request->qtyRequired(),
            $request->uom(),
            $request->isPhantom(),
            $request->notes()
        );

        return ApiResponse::success('Product relationship added successfully');
    }

    public function updateChild(int $parentId, int $dependencyId, UpdateDependencyRequest $request): array
    {
        $existing = $this->repository->findByIdForParent($parentId, $dependencyId);

        if (!$existing) {
            throw new ValidationException([
                'relationship' => ['Product relationship not found']
            ]);
        }

        $data = $request->data();

        if (isset($data['relation_type'])) {
            $childId = (int) $existing['child_id'];

            if (
                $data['relation_type'] !== $existing['relation_type'] &&
                $this->repository->relationshipExists($parentId, $childId, $data['relation_type'])
            ) {
                throw new ValidationException([
                    'relationship' => ['Product relationship already exists']
                ]);
            }
        }

        $this->repository->update($parentId, $dependencyId, $data);

        return ApiResponse::success('Product relationship updated successfully');
    }

    public function removeChild(int $parentId, int $dependencyId): array
    {
        if (!$this->repository->findByIdForParent($parentId, $dependencyId)) {
            throw new ValidationException([
                'relationship' => ['Product relationship not found']
            ]);
        }

        $this->repository->delete($parentId, $dependencyId);

        return ApiResponse::success('Product relationship removed successfully');
    }
}