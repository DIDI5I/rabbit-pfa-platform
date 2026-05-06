<?php

namespace App\Services\Chatbot\Tools\Order;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\OrderService;
use Throwable;

class RecentOrdersTool extends BaseToolHandler
{
    private const LIMIT = 5;

    public function supports(string $intent): bool
    {
        return $intent === 'recent_orders';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';

        try {
            $response = (new OrderService())->all();

            $orders = $response['data'] ?? [];
            if (!is_array($orders)) {
                $orders = [];
            }

            usort($orders, function (array $a, array $b) {
                return strtotime((string) ($b['created_at'] ?? '1970-01-01'))
                    <=> strtotime((string) ($a['created_at'] ?? '1970-01-01'));
            });

            $preview = array_slice($orders, 0, self::LIMIT);

            return $this->result(
                'recent_orders',
                'success',
                [
                    'orders' => $preview,
                    'total' => count($orders),
                    'count' => count($preview),
                    'shown' => count($preview),
                ],
                $this->meta('recent_orders', $role, 'read_only', true, count($preview))
            );
        } catch (Throwable $e) {
            return $this->result(
                'recent_orders',
                'failed',
                [],
                $this->meta('recent_orders', $role, 'read_only', true, 0),
                [
                    [
                        'code' => 'recent_orders_failed',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }
}