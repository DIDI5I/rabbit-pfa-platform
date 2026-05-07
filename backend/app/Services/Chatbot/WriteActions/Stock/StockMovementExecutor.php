<?php

namespace App\Services\Chatbot\WriteActions\Stock;

use App\Core\Auth;
use App\Services\Chatbot\WriteActions\Contracts\PendingActionExecutorInterface;
use App\Services\StockService;
use Throwable;

class StockMovementExecutor implements PendingActionExecutorInterface
{
    public function supports(array $action): bool
    {
        return ($action['tool'] ?? null) === 'record_stock_movement';
    }

    public function execute(array $identity, array $action): array
    {
        $componentId = (int) ($action['params']['component_id'] ?? 0);
        $type = strtolower(trim((string) ($action['params']['type'] ?? '')));
        $quantity = (float) ($action['params']['quantity'] ?? 0);
        $reason = strtoupper(trim((string) ($action['params']['reason'] ?? 'MANUAL_ADJUSTMENT')));
        $referenceType = $action['params']['reference_type'] ?? null;
        $referenceId = $action['params']['reference_id'] ?? null;
        $notes = $action['params']['notes'] ?? null;

        try {
            $result = (new StockService())->recordMovementByData(
                $componentId,
                $type,
                $quantity,
                $reason,
                $referenceType,
                $referenceId !== null ? (int) $referenceId : null,
                $notes,
                Auth::id()
            );

            return [
                'executed' => true,
                'answer' => "Confirmed. Stock {$type} movement of {$quantity} was recorded for component #{$componentId}.",
                'summary' => [
                    'updated' => true,
                    'action' => 'record_stock_movement',
                    'component_id' => $componentId,
                    'movement_type' => $type,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'service_message' => $result['message'] ?? null,
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'record_stock_movement',
                        'status' => 'executed',
                    ],
                ],
                'limitations' => [],
                'suggested_actions' => [
                    'Show stock for component',
                    'Show stock movements',
                ],
            ];
        } catch (Throwable $e) {
            return [
                'executed' => false,
                'answer' => "Stock movement could not be recorded for component #{$componentId}.",
                'summary' => [
                    'updated' => false,
                    'action' => 'record_stock_movement',
                    'component_id' => $componentId,
                    'movement_type' => $type,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'error' => $e->getMessage(),
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'record_stock_movement',
                        'status' => 'execution_failed',
                    ],
                ],
                'limitations' => [
                    'The stock movement was not recorded.',
                ],
                'suggested_actions' => [
                    'Show stock for component',
                ],
            ];
        }
    }
}