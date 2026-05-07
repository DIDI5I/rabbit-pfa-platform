<?php

namespace App\Services\Chatbot\WriteActions\Order;

use App\Services\Chatbot\WriteActions\Contracts\PendingActionExecutorInterface;
use App\Services\OrderService;
use Throwable;

class UpdateOrderStatusExecutor implements PendingActionExecutorInterface
{
    public function supports(array $action): bool
    {
        return ($action['tool'] ?? null) === 'update_order_status';
    }

    public function execute(array $identity, array $action): array
    {
        $orderId = (int) ($action['params']['order_id'] ?? 0);
        $newStatus = strtolower(trim((string) ($action['params']['status'] ?? '')));

        try {
            $result = (new OrderService())->updateStatusByData(
                $orderId,
                $newStatus
            );

            return [
                'executed' => true,
                'answer' => match ($newStatus) {
                    'processing' => "Confirmed. Order #{$orderId} was marked as processing and sale stock-out movements were created.",
                    'cancelled' => "Confirmed. Order #{$orderId} was cancelled and stock restore logic was applied.",
                    default => "Confirmed. Order #{$orderId} was marked as {$newStatus}.",
                },
                'summary' => [
                    'updated' => true,
                    'action' => 'update_order_status',
                    'order_id' => $orderId,
                    'new_status' => $newStatus,
                    'stock_impact' => match ($newStatus) {
                        'processing' => 'sale_stock_out_movements_created',
                        'cancelled' => 'stock_restore_logic_applied',
                        default => null,
                    },
                    'service_message' => $result['message'] ?? null,
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'update_order_status',
                        'status' => 'executed',
                    ],
                ],
                'limitations' => [],
                'suggested_actions' => [
                    'Show order details',
                    'Show orders',
                ],
            ];
        } catch (Throwable $e) {
            return [
                'executed' => false,
                'answer' => "Order #{$orderId} could not be marked as {$newStatus}.",
                'summary' => [
                    'updated' => false,
                    'action' => 'update_order_status',
                    'order_id' => $orderId,
                    'new_status' => $newStatus,
                    'error' => $e->getMessage(),
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'update_order_status',
                        'status' => 'execution_failed',
                    ],
                ],
                'limitations' => [
                    'The order was not updated.',
                ],
                'suggested_actions' => [
                    'Show order details',
                ],
            ];
        }
    }
}