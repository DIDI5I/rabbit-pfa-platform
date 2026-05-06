<?php

namespace App\Services\Chatbot\Presenters\Order;

use App\Services\Chatbot\Presenters\BaseResponsePresenter;

class RecentOrdersPresenter extends BaseResponsePresenter
{
    protected const PREVIEW_LIMIT = 5;

    public function supports(string $tool): bool
    {
        return $tool === 'recent_orders';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $orders = $toolResult['data']['orders'] ?? [];
        $total = (int) ($toolResult['data']['total'] ?? count($orders));
        $shown = (int) ($toolResult['data']['shown'] ?? count($orders));

        if ($shown === 0) {
            $answer = 'No recent orders were found.';
        } else {
            $answer = "Showing {$shown} recent order" . ($shown === 1 ? '' : 's') . ".";
        }

        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'total' => $total,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $this->orderPreview($orders, self::PREVIEW_LIMIT, $role),
            [
                'has_more' => $total > $shown,
                'total' => $total,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            ['Show order summary', 'Show pending orders', 'Open orders page']
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