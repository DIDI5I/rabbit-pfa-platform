<?php

namespace App\Services\Chatbot\Presenters\Order;

use App\Services\Chatbot\Presenters\BaseResponsePresenter;

class OrderSummaryPresenter extends BaseResponsePresenter
{
    protected const PREVIEW_LIMIT = 5;

    public function supports(string $tool): bool
    {
        return $tool === 'order_summary';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $orders = $toolResult['data']['orders'] ?? [];
        $summary = $toolResult['data']['summary'] ?? [];
        $count = (int) ($toolResult['data']['count'] ?? count($orders));
        $shown = min(self::PREVIEW_LIMIT, count($orders));

        if ($count === 0) {
            $answer = $role === 'client'
                ? 'You have no orders yet.'
                : 'No orders were found.';
        } else {
            $answer = "I found {$count} order" . ($count === 1 ? '' : 's') . ". Showing {$shown}.";
        }

        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'total' => $summary['total'] ?? $count,
                'pending' => $summary['pending'] ?? 0,
                'processing' => $summary['processing'] ?? 0,
                'shipped' => $summary['shipped'] ?? 0,
                'delivered' => $summary['delivered'] ?? 0,
                'cancelled' => $summary['cancelled'] ?? 0,
                'total_amount' => $summary['total_amount'] ?? 0,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $this->orderPreview($orders, self::PREVIEW_LIMIT, $role),
            [
                'has_more' => $count > $shown,
                'total' => $count,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $count > $shown
                ? ['Show recent orders', 'Show pending orders', 'Open orders page']
                : ['Show recent orders', 'Show pending orders', 'Open orders page']
        );
    }

    private function orderPreview(array $orders, int $limit, string $role): array
    {
        return array_map(function (array $order) use ($role) {
            return $this->sanitizeOrder($order, $role);
        }, array_slice($orders, 0, $limit));
    }

    private function sanitizeOrder(array $order, string $role): array
    {
        $items = $order['items'] ?? [];
        $itemCount = is_array($items) ? count($items) : 0;

        $base = [
            'order_id' => $order['id'] ?? null,
            'status' => $order['status'] ?? null,
            'total_amount' => $order['total_amount'] ?? null,
            'item_count' => $itemCount,
            'created_at' => $order['created_at'] ?? null,
        ];

        if ($role === 'owner') {
            $base['client_id'] = $order['client_id'] ?? null;
            $base['client_name'] = $order['client_name'] ?? null;
        }

        return $base;
    }
}