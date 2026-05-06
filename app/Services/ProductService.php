<?php

namespace App\Services;

use App\Forms\ProductRequest;
use App\Repositories\ProductRepository;
use App\Forms\StoreProductRequest;
use App\Forms\UpdateProductRequest;
use App\Core\Auth;
use App\Exceptions\ValidationException;
use App\Support\ApiResponse;

class ProductService
{
    protected ProductRepository $repository;
    protected RfqService $rfqService;

    public function __construct()
    {
        $this->repository = new ProductRepository();
        $this->rfqService = new RfqService();
    }

    public function list(ProductRequest $request): array
    {
        if (!$request->activeOnly() && Auth::role() !== 'owner') {
        throw new ValidationException([
            'access' => ['Only owners can view inactive products']
        ]);
    }
        return $this->repository->search($request);
    }

    public function show(int $id): array
    {
        $product = $this->repository->findById($id);

        if (!$product) {
            throw new ValidationException([
                'product' => ['Product not found']
            ]);
        }
        return ApiResponse::success('Product fetched successfully', $product);
    }

    public function store(StoreProductRequest $request): array
    {
        Auth::requireRole('owner');

        $product = $this->repository->create($request);

        return ApiResponse::success('Product created successfully', $product);
    }

    public function update(int $id, UpdateProductRequest $request): array
    {
        Auth::requireRole('owner');

        if (!$this->repository->findById($id)) {
            throw new ValidationException([
                'product' => ['Product not found']
            ]);
        }

        $product = $this->repository->update($id, $request);

        $autoRfq = [
            'created' => false,
            'rfq_id' => null
        ];

        if ($request->has('stock_qty')) {
            $autoRfq = $this->rfqService->createAutoDraftForLowStock(
                $product,
                Auth::id()
            );
        }

        return ApiResponse::success('Product updated successfully', [
            'product' => $product,
            'auto_rfq' => $autoRfq
        ]);
    }

    public function delete(int $id): array
    {
        Auth::requireRole('owner');

        if (!$this->repository->findById($id)) {
            throw new ValidationException([
                'product' => ['Product not found']
            ]);
        }

        $this->repository->softDelete($id);

        return ApiResponse::success('Product deleted successfully');
    }
}