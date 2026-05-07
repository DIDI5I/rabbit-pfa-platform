<?php

namespace App\Services\Chatbot\WriteActions\Stock;

use App\Core\Auth;
use App\Services\Chatbot\WriteActions\Contracts\PendingActionExecutorInterface;
use App\Services\StockService;
use Throwable;

class SetStockLevelExecutor implements PendingActionExecutorInterface
{
    public function supports(array $action): bool
    {
        return ($action['tool'] ?? null) === 'set_stock_level';
    }

    public function execute(array $identity, array $action): array
    {
        $componentId = (int) ($action['params']['component_id'] ?? 0);
        $targetStock = (float) ($action['params']['target_stock'] ?? 0);
        $movementType = strtolower(trim((string) ($action['params']['movement_type'] ?? '')));
        $movementQuantity = (float) ($action['params']['movement_quantity'] ?? 0);
        $reason = strtoupper(trim((string) ($action['params']['reason'] ?? 'MANUAL_ADJUSTMENT')));
        $referenceType = $action['params']['reference_type'] ?? null;
        $referenceId = $action['params']['reference_id'] ?? null;
        $notes = $action['params']['notes'] ?? null;

        try {
            $result = (new StockService())->recordMovementByData(
                $componentId,
                $movementType,
                $movementQuantity,
                $reason,
                $referenceType,
                $referenceId !== null ? (int) $referenceId : null,
                $notes,
                Auth::id()
            );

            return [
                'executed' => true,
                'answer' => "Confirmed. Component #{$componentId} stock was adjusted to {$targetStock}.",
                'summary' => [
                    'updated' => true,
                    'action' => 'set_stock_level',
                    'component_id' => $componentId,
                    'target_stock' => $targetStock,
                    'movement_type' => $movementType,
                    'movement_quantity' => $movementQuantity,
                    'reason' => $reason,
                    'service_message' => $result['message'] ?? null,
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'set_stock_level',
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
                'answer' => "Component #{$componentId} stock could not be adjusted to {$targetStock}.",
                'summary' => [
                    'updated' => false,
                    'action' => 'set_stock_level',
                    'component_id' => $componentId,
                    'target_stock' => $targetStock,
                    'movement_type' => $movementType,
                    'movement_quantity' => $movementQuantity,
                    'reason' => $reason,
                    'error' => $e->getMessage(),
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'set_stock_level',
                        'status' => 'execution_failed',
                    ],
                ],
                'limitations' => [
                    'The stock level was not adjusted.',
                ],
                'suggested_actions' => [
                    'Show stock for component',
                ],
            ];
        }
    }
}