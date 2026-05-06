<?php

namespace App\Services\Chatbot\Tools\Order;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\OrderService;
use Throwable;

class OrderSummaryTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'order_summary';
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

            return $this->result(
                'order_summary',
                'success',
                [
                    'orders' => $orders,
                    'summary' => $this->summary($orders),
                    'count' => count($orders),
                ],
                $this->meta('order_summary', $role, 'read_only', true, count($orders))
            );
        } catch (Throwable $e) {
            return $this->result(
                'order_summary',
                'failed',
                [],
                $this->meta('order_summary', $role, 'read_only', true, 0),
                [
                    [
                        'code' => 'order_summary_failed',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }

    private function summary(array $orders): array
    {
        $summary = [
            'total' => count($orders),
            'pending' => 0,
            'processing' => 0,
            'shipped' => 0,
            'delivered' => 0,
            'cancelled' => 0,
            'total_amount' => 0.0,
        ];

        foreach ($orders as $order) {
            $status = (string) ($order['status'] ?? 'unknown');

            if (array_key_exists($status, $summary)) {
                $summary[$status]++;
            }

            $summary['total_amount'] += (float) ($order['total_amount'] ?? 0);
        }

        $summary['total_amount'] = round($summary['total_amount'], 2);

        return $summary;
    }
}