<?php

namespace App\Services\Chatbot\Tools\Order;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\OrderService;
use Throwable;

class OrdersByStatusTool extends BaseToolHandler
{
    private const LIMIT = 5;

    public function supports(string $intent): bool
    {
        return $intent === 'orders_by_status';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $status = strtolower(trim((string) ($params['status'] ?? '')));

        if ($status === '') {
            return $this->result(
                'orders_by_status',
                'missing_params',
                [
                    'missing_params' => ['status'],
                ],
                $this->meta('orders_by_status', $role, 'read_only', true, 0),
                [
                    [
                        'code' => 'missing_status',
                        'message' => 'status is required.',
                    ],
                ]
            );
        }

        try {
            $response = (new OrderService())->all();

            $orders = $response['data'] ?? [];
            if (!is_array($orders)) {
                $orders = [];
            }

            $filtered = array_values(array_filter($orders, function (array $order) use ($status) {
                return strtolower((string) ($order['status'] ?? '')) === $status;
            }));

            usort($filtered, function (array $a, array $b) {
                return strtotime((string) ($b['created_at'] ?? '1970-01-01'))
                    <=> strtotime((string) ($a['created_at'] ?? '1970-01-01'));
            });

            $preview = array_slice($filtered, 0, self::LIMIT);

            return $this->result(
                'orders_by_status',
                'success',
                [
                    'status' => $status,
                    'orders' => $preview,
                    'total' => count($filtered),
                    'count' => count($preview),
                    'shown' => count($preview),
                ],
                $this->meta('orders_by_status', $role, 'read_only', true, count($preview))
            );
        } catch (Throwable $e) {
            return $this->result(
                'orders_by_status',
                'failed',
                [
                    'status' => $status,
                ],
                $this->meta('orders_by_status', $role, 'read_only', true, 0),
                [
                    [
                        'code' => 'orders_by_status_failed',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }
}