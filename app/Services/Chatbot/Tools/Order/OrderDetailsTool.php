<?php

namespace App\Services\Chatbot\Tools\Order;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\OrderService;
use Throwable;

class OrderDetailsTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'order_details';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $orderId = isset($params['order_id']) ? (int) $params['order_id'] : 0;

        if ($orderId <= 0) {
            return $this->result(
                'order_details',
                'missing_params',
                [
                    'missing_params' => ['order_id'],
                ],
                $this->meta('order_details', $role, 'read_only', true, 0),
                [
                    [
                        'code' => 'missing_order_id',
                        'message' => 'order_id is required.',
                    ],
                ]
            );
        }

        try {
            $response = (new OrderService())->find($orderId);

            $order = $response['data'] ?? null;

            if (!is_array($order)) {
                return $this->result(
                    'order_details',
                    'not_found',
                    [
                        'order_id' => $orderId,
                    ],
                    $this->meta('order_details', $role, 'read_only', true, 0),
                    [
                        [
                            'code' => 'order_not_found',
                            'message' => 'Order not found.',
                        ],
                    ]
                );
            }

            return $this->result(
                'order_details',
                'success',
                [
                    'order_id' => $orderId,
                    'order' => $order,
                    'count' => 1,
                ],
                $this->meta('order_details', $role, 'read_only', true, 1)
            );
        } catch (Throwable $e) {
            return $this->result(
                'order_details',
                'failed',
                [
                    'order_id' => $orderId,
                ],
                $this->meta('order_details', $role, 'read_only', true, 0),
                [
                    [
                        'code' => 'order_details_failed',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }
}