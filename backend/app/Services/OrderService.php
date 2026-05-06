<?php

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Forms\StoreOrderRequest;
use App\Forms\UpdateOrderStatusRequest;
use App\Forms\StoreStockMovementRequest;
use App\Exceptions\ValidationException;
use App\Support\ApiResponse;
use App\Core\Auth;

class OrderService
{
    private OrderRepository $orders;
    private ProductRepository $products;
    private UserRepository $users;
    private StockService $stockService;

    public function __construct()
    {
        $this->orders = new OrderRepository();
        $this->products = new ProductRepository();
        $this->users = new UserRepository();
        $this->stockService = new StockService();
    }

    public function all(): array
    {
        $role = Auth::role();

        if ($role === 'owner') {
            $orders = $this->orders->findAll();
        } elseif ($role === 'client') {
            $clientId = Auth::id();

            $orders = array_filter(
                $this->orders->findAll(),
                fn ($order) => (int) $order['client_id'] === $clientId
            );

            $orders = array_values($orders);
        } else {
            throw new ValidationException([
                'auth' => ['You are not allowed to view orders.'],
            ]);
        }

        foreach ($orders as &$order) {
            $order['items'] = $this->orders->findItemsByOrderId($order['id']);
        }

        unset($order);

        return ApiResponse::success('Orders fetched successfully', $orders);
    }

    public function find(int $orderId): array
    {
        $order = $this->orders->findById($orderId);

        if (!$order) {
            throw new ValidationException([
                'order_id' => ['Order not found.'],
            ]);
        }

        $this->authorizeOrderAccess($order);

        $order['items'] = $this->orders->findItemsByOrderId($orderId);

        return ApiResponse::success('Order fetched successfully', $order);
    }

    public function create(StoreOrderRequest $request): array
    {
        $data = $request->data();

        $clientId = $this->resolveClientIdForOrderCreation($data);

        if (!$this->users->findById($clientId)) {
            throw new ValidationException([
                'client_id' => ['Client not found.'],
            ]);
        }

        $items = $this->prepareItems($data['items']);

        $totalAmount = $this->calculateTotalAmount($items);

        $orderId = $this->orders->createOrder([
            'client_id' => $clientId,
            'total_amount' => $totalAmount,
            'shipping_address' => $data['shipping_address'],
            'notes' => $data['notes'],
        ]);

        foreach ($items as $item) {
            $this->orders->createOrderItem($orderId, $item);
        }

        $order = $this->orders->findById($orderId);
        $order['items'] = $this->orders->findItemsByOrderId($orderId);

        $this->notifyOwnerOrderCreated($order);

        return ApiResponse::success('Order created successfully', $order);
    }

    public function updateStatus(int $orderId, UpdateOrderStatusRequest $request): array
    {
        $this->authorizeOwnerOnly();

        $order = $this->orders->findById($orderId);

        if (!$order) {
            throw new ValidationException([
                'order_id' => ['Order not found.'],
            ]);
        }

        $newStatus = $request->status();

        if ($newStatus === 'processing') {
            $this->createSaleStockMovements($orderId);
        }

        if ($newStatus === 'cancelled') {
            $this->restoreStockForCancelledOrder($orderId);
        }

        $this->orders->updateStatus($orderId, $newStatus);

        $updatedOrder = $this->orders->findById($orderId);
        $updatedOrder['items'] = $this->orders->findItemsByOrderId($orderId);

        $this->notifyClientOrderStatusChanged($updatedOrder, $order['status'], $newStatus);

        return ApiResponse::success('Order status updated successfully', $updatedOrder);
    }

    private function prepareItems(array $items): array
    {
        $prepared = [];

        foreach ($items as $item) {
            if (!$this->products->findById($item['component_id'])) {
                throw new ValidationException([
                    'component_id' => ['Product not found: ' . $item['component_id']],
                ]);
            }

            $unitPrice = $item['unit_price'];

            if ($unitPrice === null) {
                $unitPrice = $this->orders->findPreferredUnitCost($item['component_id']);
            }

            if ($unitPrice === null) {
                throw new ValidationException([
                    'unit_price' => ['Unit price is required when no preferred supplier cost exists.'],
                ]);
            }

            $prepared[] = [
                'component_id' => $item['component_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'supplier_id' => $item['supplier_id'],
            ];
        }

        return $prepared;
    }

    private function calculateTotalAmount(array $items): float
    {
        $total = 0;

        foreach ($items as $item) {
            $total += $item['quantity'] * $item['unit_price'];
        }

        return round($total, 2);
    }

    private function createSaleStockMovements(int $orderId): void
    {
        if ($this->orders->hasSaleStockMovement($orderId)) {
            return;
        }

        $items = $this->orders->findItemsByOrderId($orderId);

        foreach ($items as $item) {
            $request = new StoreStockMovementRequest([
                'component_id' => $item['component_id'],
                'type' => 'out',
                'quantity' => $item['quantity'],
                'reason' => 'SALE',
                'reference_type' => 'order',
                'reference_id' => $orderId,
                'notes' => 'Stock removed for order #' . $orderId,
            ]);

            $request->validate();

            $this->stockService->recordMovement($request, null);
        }
    }

    private function restoreStockForCancelledOrder(int $orderId): void
    {
        if (!$this->orders->hasSaleStockMovement($orderId)) {
            return;
        }

        if ($this->orders->hasRestoreStockMovement($orderId)) {
            return;
        }

        $items = $this->orders->findItemsByOrderId($orderId);

        foreach ($items as $item) {
            $request = new StoreStockMovementRequest([
                'component_id' => $item['component_id'],
                'type' => 'in',
                'quantity' => $item['quantity'],
                'reason' => 'CANCELLED_ORDER_RESTORE',
                'reference_type' => 'order',
                'reference_id' => $orderId,
                'notes' => 'Stock restored after order #' . $orderId . ' cancellation',
            ]);

            $request->validate();

            $this->stockService->recordMovement($request, null);
        }
    }

    private function notifyOwnerOrderCreated(array $order): void
    {
        $notifications = new NotificationService();

        $orderId = (int) $order['id'];
        $clientName = $order['client_name'] ?? 'Client inconnu';
        $totalAmount = (float) $order['total_amount'];

        $notifications->notifyRole(
            'owner',
            'ORDER_CREATED',
            'Nouvelle commande',
            "Une nouvelle commande #{$orderId} a été créée par {$clientName}. Montant total: {$totalAmount} MAD.",
            'order',
            $orderId
        );
    }

    private function notifyClientOrderStatusChanged(
        array $order,
        string $oldStatus,
        string $newStatus
    ): void {
        if ($oldStatus === $newStatus) {
            return;
        }

        $notifications = new NotificationService();

        $orderId = (int) $order['id'];
        $clientId = (int) $order['client_id'];

        $notifications->notifyUser(
            $clientId,
            'ORDER_STATUS_CHANGED',
            'Statut de commande modifié',
            "Le statut de votre commande #{$orderId} est passé de {$oldStatus} à {$newStatus}.",
            'order',
            $orderId
        );
    }

    private function resolveClientIdForOrderCreation(array $data): int
    {
        $role = Auth::role();

        if ($role === 'client') {
            return Auth::id();
        }

        if ($role === 'owner') {
            if (empty($data['client_id'])) {
                throw new ValidationException([
                    'client_id' => ['client_id is required when owner creates an order.'],
                ]);
            }

            return (int) $data['client_id'];
        }

        throw new ValidationException([
            'auth' => ['You are not allowed to create orders.'],
        ]);
    }

    private function authorizeOrderAccess(array $order): void
    {
        $role = Auth::role();

        if ($role === 'owner') {
            return;
        }

        if ($role === 'client' && (int) $order['client_id'] === Auth::id()) {
            return;
        }

        throw new ValidationException([
            'auth' => ['You are not allowed to access this order.'],
        ]);
    }

    private function authorizeOwnerOnly(): void
    {
        if (Auth::role() === 'owner') {
            return;
        }

        throw new ValidationException([
            'auth' => ['Only owner can update order status.'],
        ]);
    }
}