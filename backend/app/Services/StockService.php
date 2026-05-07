<?php

namespace App\Services;

use App\Repositories\StockMovementRepository;
use App\Repositories\ProductRepository;
use App\Forms\StoreStockMovementRequest;
use App\Exceptions\ValidationException;
use App\Support\ApiResponse;

class StockService
{
    protected StockMovementRepository $repository;
    protected ProductRepository $products;

    public function __construct()
    {
        $this->repository = new StockMovementRepository();
        $this->products = new ProductRepository();
    }

    public function recordMovement(StoreStockMovementRequest $request, ?int $createdBy = null): array
    {
        $componentId = $request->componentId();

        $product = $this->products->findById($componentId);

        if (!$product) {
            throw new ValidationException([
                'component_id' => ['Product not found.'],
            ]);
        }

        $beforeStock = $this->repository->currentStock($componentId);

        $beforeStatus = $this->resolveStockStatus(
            $beforeStock,
            (float) $product['low_stock_threshold']
        );

        if ($request->type() === 'out') {
            if ($beforeStock < $request->quantity()) {
                throw new ValidationException([
                    'stock' => ['Insufficient stock for this OUT movement.'],
                ]);
            }
        }

        $data = $request->data();
        $data['created_by'] = $createdBy;

        $this->repository->create($data);

        $afterStock = $this->repository->currentStock($componentId);

        $afterStatus = $this->resolveStockStatus(
            $afterStock,
            (float) $product['low_stock_threshold']
        );

        $this->notifyStockAlertIfNeeded(
            $product,
            $beforeStatus,
            $afterStatus,
            $afterStock
        );

        return ApiResponse::success('Stock movement recorded successfully');
    }

    public function getStock(int $componentId): array
    {
        if (!$this->products->findById($componentId)) {
            throw new ValidationException([
                'component_id' => ['Product not found.'],
            ]);
        }

        $summary = $this->repository->stockSummary($componentId);

        if (!$summary) {
            throw new ValidationException([
                'component_id' => ['Product not found.'],
            ]);
        }

        $summary['stock_status'] = $this->resolveStockStatus(
            $summary['current_stock'],
            $summary['low_stock_threshold']
        );

        return ApiResponse::success('Stock fetched successfully', $summary);
    }

    public function getMovements(int $componentId): array
    {
        if (!$this->products->findById($componentId)) {
            throw new ValidationException([
                'component_id' => ['Product not found.'],
            ]);
        }

        return ApiResponse::success(
            'Stock movements fetched successfully',
            $this->repository->findByComponent($componentId)
        );
    }

    private function notifyStockAlertIfNeeded(
        array $product,
        string $beforeStatus,
        string $afterStatus,
        float $currentStock
    ): void {
        if ($beforeStatus === $afterStatus) {
            return;
        }

        if (!in_array($afterStatus, ['LOW_STOCK', 'OUT_OF_STOCK'], true)) {
            return;
        }

        $notifications = new NotificationService();

        $productName = $product['name'] ?? 'Produit';
        $sku = $product['sku'] ?? 'N/A';
        $threshold = (float) ($product['low_stock_threshold'] ?? 0);
        $productId = (int) $product['id'];

        if ($afterStatus === 'LOW_STOCK') {
            $notifications->notifyRole(
                'owner',
                'LOW_STOCK_ALERT',
                'Stock faible',
                "Le produit {$productName} ({$sku}) est passé en stock faible. Stock actuel: {$currentStock}. Seuil: {$threshold}.",
                'product',
                $productId
            );

            return;
        }

        if ($afterStatus === 'OUT_OF_STOCK') {
            $notifications->notifyRole(
                'owner',
                'OUT_OF_STOCK_ALERT',
                'Rupture de stock',
                "Le produit {$productName} ({$sku}) est maintenant en rupture de stock. Stock actuel: {$currentStock}.",
                'product',
                $productId
            );
        }
    }

    private function resolveStockStatus(float $currentStock, float $threshold): string
    {
        if ($currentStock <= 0) {
            return 'OUT_OF_STOCK';
        }

        if ($currentStock <= $threshold) {
            return 'LOW_STOCK';
        }

        return 'OK';
    }

    public function recordMovementByData(
        int $componentId,
        string $type,
        float $quantity,
        string $reason = 'MANUAL_ADJUSTMENT',
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $createdBy = null
    ): array {
        $request = new StoreStockMovementRequest([
            'component_id' => $componentId,
            'type' => $type,
            'quantity' => $quantity,
            'reason' => $reason,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
        ]);

        $request->validate();

        return $this->recordMovement($request, $createdBy);
    }
}