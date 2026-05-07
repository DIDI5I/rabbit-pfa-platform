<?php

namespace App\Services\Chatbot\WriteActions\Order;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\Chatbot\WriteActions\PendingActionStore;
use App\Services\Chatbot\WriteActions\WriteActionResponseBuilder;
use App\Services\OrderService;

class UpdateOrderStatusPreviewTool extends BaseToolHandler
{
    private const CHATBOT_SUPPORTED_STATUSES = [
        'processing',
        'shipped',
        'delivered',
        'cancelled',
    ];

    private const STOCK_IMPACT_STATUSES = [
        'processing',
        'cancelled',
    ];

    public function supports(string $intent): bool
    {
        return $intent === 'update_order_status';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $orderId = (int) ($params['order_id'] ?? 0);
        $newStatus = strtolower(trim((string) ($params['status'] ?? '')));

       if (!in_array($newStatus, self::CHATBOT_SUPPORTED_STATUSES, true)) {
            return [
                'message' => 'Order action not supported.',
                'data' => [
                    'answer' => "I cannot update order #{$orderId} to '{$newStatus}' through chatbot yet.",
                    'ai_refined' => false,
                    'intent' => 'update_order_status',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'write_action',
                    'summary' => [
                        'action_required' => false,
                        'confirmation_required' => false,
                        'order_id' => $orderId,
                        'requested_status' => $newStatus,
                        'supported_statuses' => self::CHATBOT_SUPPORTED_STATUSES,
                    ],
                    'items_preview' => [],
                    'result_meta' => [
                        'pending_action' => false,
                        'executed' => false,
                    ],
                    'sources' => [
                        [
                            'tool' => 'update_order_status',
                            'status' => 'unsupported_status',
                        ],
                    ],
                    'limitations' => [
                       'Only processing, shipped, delivered, and cancelled statuses are currently supported through chatbot write actions.',
                    ],
                    'suggested_actions' => [
                        'Show order details',
                    ],
                ],
            ];
        }

        $orderResponse = (new OrderService())->find($orderId);
        $order = $orderResponse['data'] ?? null;

        if (!is_array($order)) {
            return [
                'message' => 'Order not found.',
                'data' => [
                    'answer' => "Order #{$orderId} was not found or is not accessible.",
                    'ai_refined' => false,
                    'intent' => 'update_order_status',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'write_action',
                    'summary' => [
                        'action_required' => false,
                        'confirmation_required' => false,
                        'order_id' => $orderId,
                    ],
                    'items_preview' => [],
                    'result_meta' => [
                        'pending_action' => false,
                        'executed' => false,
                    ],
                    'sources' => [
                        [
                            'tool' => 'update_order_status',
                            'status' => 'not_found_or_not_accessible',
                        ],
                    ],
                    'limitations' => [],
                    'suggested_actions' => [
                        'Show orders',
                    ],
                ],
            ];
        }

        $currentStatus = $order['status'] ?? null;

        if ($currentStatus === $newStatus) {
            return [
                'message' => 'Order action not needed.',
                'data' => [
                    'answer' => "Order #{$orderId} is already {$newStatus}.",
                    'ai_refined' => false,
                    'intent' => 'update_order_status',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'write_action',
                    'summary' => [
                        'action_required' => false,
                        'confirmation_required' => false,
                        'order_id' => $orderId,
                        'current_status' => $currentStatus,
                        'requested_status' => $newStatus,
                    ],
                    'items_preview' => [],
                    'result_meta' => [
                        'pending_action' => false,
                        'executed' => false,
                    ],
                    'sources' => [
                        [
                            'tool' => 'update_order_status',
                            'status' => 'no_action_needed',
                        ],
                    ],
                    'limitations' => [],
                    'suggested_actions' => [
                        'Show order details',
                    ],
                ],
            ];
        }

        $items = $order['items'] ?? [];

        $sideEffects = [
            'client_notified',
        ];

        $riskLevel = 'medium';

        if ($newStatus === 'processing') {
            $sideEffects[] = 'sale_stock_out_movements_created';
            $riskLevel = 'high';

            if (empty($items)) {
                return [
                    'message' => 'Order action not allowed.',
                    'data' => [
                        'answer' => "Order #{$orderId} cannot be marked as processing because it has no items.",
                        'ai_refined' => false,
                        'intent' => 'update_order_status',
                        'confidence' => 'high',
                        'role' => $identity['role'] ?? 'guest',
                        'operation_type' => 'write_action',
                        'summary' => [
                            'action_required' => false,
                            'confirmation_required' => false,
                            'order_id' => $orderId,
                            'current_status' => $currentStatus,
                            'requested_status' => $newStatus,
                        ],
                        'items_preview' => [],
                        'result_meta' => [
                            'pending_action' => false,
                            'executed' => false,
                        ],
                        'sources' => [
                            [
                                'tool' => 'update_order_status',
                                'status' => 'missing_order_items',
                            ],
                        ],
                        'limitations' => [
                            'Processing an order requires order items because stock-out movements are created from the items.',
                        ],
                        'suggested_actions' => [
                            'Show order details',
                        ],
                    ],
                ];
            }
        }

        if ($newStatus === 'cancelled') {
            $sideEffects[] = 'stock_restore_checked';
            $riskLevel = 'high';

            if (!empty($items)) {
                $sideEffects[] = 'possible_stock_restore_movements_created';
            }

            if (empty($items)) {
                return [
                    'message' => 'Order action not allowed.',
                    'data' => [
                        'answer' => "Order #{$orderId} cannot be cancelled through chatbot because it has no items.",
                        'ai_refined' => false,
                        'intent' => 'update_order_status',
                        'confidence' => 'high',
                        'role' => $identity['role'] ?? 'guest',
                        'operation_type' => 'write_action',
                        'summary' => [
                            'action_required' => false,
                            'confirmation_required' => false,
                            'order_id' => $orderId,
                            'current_status' => $currentStatus,
                            'requested_status' => $newStatus,
                        ],
                        'items_preview' => [],
                        'result_meta' => [
                            'pending_action' => false,
                            'executed' => false,
                        ],
                        'sources' => [
                            [
                                'tool' => 'update_order_status',
                                'status' => 'missing_order_items',
                            ],
                        ],
                        'limitations' => [
                            'Cancelling an order through chatbot requires order items so stock restore impact can be previewed.',
                        ],
                        'suggested_actions' => [
                            'Show order details',
                        ],
                    ],
                ];
            }
        }

        $action = [
            'intent' => 'update_order_status',
            'tool' => 'update_order_status',
            'title' => 'Update order status',
            'description' => match ($newStatus) {
                'processing' => "I can mark order #{$orderId} as processing. This will create sale stock-out movements and notify the client.",
                'cancelled' => "I can cancel order #{$orderId}. This may restore stock from previous sale movements and notify the client.",
                default => "I can mark order #{$orderId} as {$newStatus}. This will update the order status and notify the client.",
            },
            'params' => [
                'order_id' => $orderId,
                'status' => $newStatus,
            ],

            'preview' => [
                [
                    'type' => 'order_status_change',
                    'order_id' => $orderId,
                    'current_status' => $currentStatus,
                    'new_status' => $newStatus,
                    'client_id' => $order['client_id'] ?? null,
                    'client_name' => $order['client_name'] ?? null,
                    'total_amount' => $order['total_amount'] ?? null,
                    'stock_impact' => match ($newStatus) {
                        'processing' => [
                            'type' => 'sale_stock_out',
                            'items' => array_map(
                                fn (array $item): array => [
                                    'component_id' => $item['component_id'] ?? null,
                                    'component_name' => $item['component_name'] ?? null,
                                    'component_sku' => $item['component_sku'] ?? null,
                                    'quantity' => $item['quantity'] ?? null,
                                ],
                                $items
                            ),
                        ],
                        'cancelled' => [
                            'type' => 'possible_stock_restore',
                            'items' => array_map(
                                fn (array $item): array => [
                                    'component_id' => $item['component_id'] ?? null,
                                    'component_name' => $item['component_name'] ?? null,
                                    'component_sku' => $item['component_sku'] ?? null,
                                    'quantity' => $item['quantity'] ?? null,
                                ],
                                $items
                            ),
                            'note' => 'Stock will be restored only if this order previously generated sale stock-out movements and has not already been restored.',
                        ],
                        default => null,
                    },
                    'side_effects' => $sideEffects,
                ],
            ],
            'risk_level' => $riskLevel,
            'ttl_seconds' => 300,
        ];

        $store = new PendingActionStore();
        $store->put($identity, $action);

        $pending = $store->get($identity);

        return (new WriteActionResponseBuilder())->preview(
            $identity,
            $pending ?? $action
        );
    }
}