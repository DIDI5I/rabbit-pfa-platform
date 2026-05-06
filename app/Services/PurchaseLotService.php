<?php

namespace App\Services;

use App\Core\App;
use App\Core\Database;
use App\Forms\StorePurchaseLotRequest;
use App\Forms\StoreStockMovementRequest;
use App\Repositories\ProductRepository;
use App\Repositories\PurchaseLotRepository;
use App\Repositories\SupplierRepository;
use App\Exceptions\ValidationException;
use App\Support\ApiResponse;
use App\Services\NotificationService;
use Throwable;

class PurchaseLotService
{
    public function __construct(
        private PurchaseLotRepository $purchaseLotRepository = new PurchaseLotRepository(),
        private ProductRepository $productRepository = new ProductRepository(),
        private SupplierRepository $supplierRepository = new SupplierRepository(),
        private StockService $stockService = new StockService()
    ) {
    }

    public function create(StorePurchaseLotRequest $request, ?int $createdBy = null): array
    {
        if (!$this->productRepository->findById($request->componentId())) {
            throw new ValidationException([
                'component_id' => ['Product does not exist.'],
            ]);
        }

        if (!$this->supplierRepository->findById($request->supplierId())) {
            throw new ValidationException([
                'supplier_id' => ['Supplier does not exist.'],
            ]);
        }

        if (
            $request->referenceType() !== null &&
            $request->referenceId() !== null &&
            $this->purchaseLotRepository->existsByReference($request->referenceType(), $request->referenceId())
        ) {
            throw new ValidationException([
                'reference' => ['A purchase lot already exists for this reference.'],
            ]);
        }

        $database = App::resolve(Database::class);
        $pdo = $database->connection();

        try {
            $pdo->beginTransaction();

            $purchaseLot = $this->createWithoutTransaction($request, $createdBy);

            $pdo->commit();

            return ApiResponse::success('Purchase lot created successfully', $purchaseLot);

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }

           
    }

    public function all(): array
    {
        return ApiResponse::success(
            'Purchase lots fetched successfully',
            $this->purchaseLotRepository->findAll()
        );
    }

    public function find(int $id): array
    {
        $purchaseLot = $this->purchaseLotRepository->findById($id);

        if (!$purchaseLot) {
            throw new ValidationException([
                'purchase_lot_id' => ['Purchase lot not found.'],
            ]);
        }

        return ApiResponse::success('Purchase lot fetched successfully', $purchaseLot);
    }

    public function byProduct(int $productId): array
    {
        if (!$this->productRepository->findById($productId)) {
            throw new ValidationException([
                'component_id' => ['Product does not exist.'],
            ]);
        }

        return ApiResponse::success(
            'Product purchase lots fetched successfully',
            $this->purchaseLotRepository->findByComponent($productId)
        );
    }

    public function finalize(int $purchaseLotId, array $costData, ?int $createdBy = null): array
    {
        $purchaseLot = $this->purchaseLotRepository->findById($purchaseLotId);

        if (!$purchaseLot) {
            throw new ValidationException([
                'purchase_lot_id' => ['Purchase lot not found.'],
            ]);
        }

        if ($purchaseLot['status'] === 'cancelled') {
            throw new ValidationException([
                'status' => ['Cancelled purchase lots cannot be finalized.'],
            ]);
        }

        if ($purchaseLot['status'] === 'finalized') {
            throw new ValidationException([
                'status' => ['Purchase lot is already finalized.'],
            ]);
        }

        $transportCost = $this->optionalCost($costData, 'transport_cost');
        $customsCost = $this->optionalCost($costData, 'customs_cost');
        $handlingCost = $this->optionalCost($costData, 'handling_cost');
        $packagingCost = $this->optionalCost($costData, 'packaging_cost');
        $orderPreparationCost = $this->optionalCost($costData, 'order_preparation_cost');
        $otherCost = $this->optionalCost($costData, 'other_cost');

        $totalPurchaseCost = round(
            $purchaseLot['supplier_total_price']
            + $transportCost
            + $customsCost
            + $handlingCost
            + $packagingCost
            + $orderPreparationCost
            + $otherCost,
            2
        );

        $unitPurchaseCost = round(
            $totalPurchaseCost / $purchaseLot['quantity_received'],
            4
        );

        $database = App::resolve(Database::class);
        $pdo = $database->connection();

        try {
            $pdo->beginTransaction();

            $this->purchaseLotRepository->updateCostsAndStatus($purchaseLotId, [
                'transport_cost' => $transportCost,
                'customs_cost' => $customsCost,
                'handling_cost' => $handlingCost,
                'packaging_cost' => $packagingCost,
                'order_preparation_cost' => $orderPreparationCost,
                'other_cost' => $otherCost,
                'total_purchase_cost' => $totalPurchaseCost,
                'unit_purchase_cost' => $unitPurchaseCost,
                'status' => 'finalized',
            ]);

            if (!$this->purchaseLotRepository->hasPurchaseReceivedStockMovement($purchaseLotId)) {
                $stockRequest = new StoreStockMovementRequest([
                    'component_id' => $purchaseLot['component_id'],
                    'type' => 'in',
                    'quantity' => $purchaseLot['quantity_received'],
                    'reason' => 'PURCHASE_RECEIVED',
                    'reference_type' => 'purchase_lot',
                    'reference_id' => $purchaseLotId,
                    'notes' => 'Stock IN generated after finalizing purchase lot #' . $purchaseLotId,
                ]);

                $stockRequest->validate();

                $this->stockService->recordMovement($stockRequest, $createdBy);
            }

            $updatedPurchaseLot = $this->purchaseLotRepository->findById($purchaseLotId);

            $pdo->commit();
            $notificationService = new NotificationService();

            $notificationService->notifyRole(
                'owner',
                'PURCHASE_LOT_FINALIZED',
                'Lot d’achat finalisé',
                'Un lot d’achat a été finalisé avec ses coûts réels.',
                'purchase_lot',
                $purchaseLotId
            );

            $notificationService->notifyRole(
                'owner',
                'STOCK_IN_PURCHASE_RECEIVED',
                'Stock reçu',
                'Le stock a été augmenté après la réception d’un lot d’achat.',
                'purchase_lot',
                $purchaseLotId
            );
            return ApiResponse::success(
                'Purchase lot finalized successfully',
                $updatedPurchaseLot
            );

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }
    private function optionalCost(array $data, string $field): float
    {
        if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
            return 0.0;
        }

        if (!is_numeric($data[$field])) {
            throw new ValidationException([
                $field => ["{$field} must be numeric."],
            ]);
        }

        if ((float) $data[$field] < 0) {
            throw new ValidationException([
                $field => ["{$field} cannot be negative."],
            ]);
        }

        return (float) $data[$field];
    }

    public function createFromAcceptedRfq(array $rfq, ?int $createdBy = null): array
    {
        if (
            $this->purchaseLotRepository->existsByReference(
                'rfq',
                (int) $rfq['id']
            )
        ) {
            throw new ValidationException([
                'rfq' => ['A purchase lot already exists for this RFQ.'],
            ]);
        }

        $quotedPrice = isset($rfq['quoted_price'])
            ? (float) $rfq['quoted_price']
            : null;

        if ($quotedPrice === null || $quotedPrice < 0) {
            throw new ValidationException([
                'quoted_price' => ['Accepted RFQ must have a valid quoted price.'],
            ]);
        }

        $request = new StorePurchaseLotRequest([
            'component_id' => (int) $rfq['component_id'],
            'supplier_id' => (int) $rfq['supplier_id'],
            'quantity_received' => (float) $rfq['quantity_requested'],
            'supplier_unit_price' => $quotedPrice,

            'transport_cost' => 0,
            'customs_cost' => 0,
            'handling_cost' => 0,
            'packaging_cost' => 0,
            'order_preparation_cost' => 0,
            'other_cost' => 0,

            'status' => 'draft',
            'purchase_date' => date('Y-m-d'),
            'reference_type' => 'rfq',
            'reference_id' => (int) $rfq['id'],
            'notes' => 'Draft purchase lot generated from accepted RFQ #' . $rfq['id'],
        ]);

        $request->validate();

        return $this->createWithoutTransaction($request, $createdBy);
    }

    public function createWithoutTransaction(StorePurchaseLotRequest $request, ?int $createdBy = null): array
    {
        $data = $request->data();
        $data['created_by'] = $createdBy;

        $purchaseLotId = $this->purchaseLotRepository->create($data);

        return $this->purchaseLotRepository->findById($purchaseLotId);
    }
        
}