<?php

namespace App\Services\Chatbot\Presenters\Order;

use App\Services\Chatbot\Presenters\BaseResponsePresenter;

class OrderDetailsPresenter extends BaseResponsePresenter
{
    protected const ITEM_LIMIT = 5;

    public function supports(string $tool): bool
    {
        return $tool === 'order_details';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $order = $toolResult['data']['order'] ?? null;

        if (($toolResult['status'] ?? null) !== 'success' || !is_array($order)) {
            return $this->success(
                'I could not find that order or you do not have access to it.',
                $toolResult,
                $role,
                [
                    'found' => false,
                    'order_id' => $toolResult['data']['order_id'] ?? null,
                ],
                [],
                [
                    'has_more' => false,
                ],
                ['Show recent orders', 'Open orders page']
            );
        }

        $orderId = $order['id'] ?? $toolResult['data']['order_id'] ?? null;
        $status = $order['status'] ?? null;
        $totalAmount = $order['total_amount'] ?? null;
        $items = is_array($order['items'] ?? null) ? $order['items'] : [];
        $itemCount = count($items);
        $shown = min(self::ITEM_LIMIT, $itemCount);

        $answer = "Order #{$orderId} is currently {$status}.";
        $answer .= " It has {$itemCount} item" . ($itemCount === 1 ? '' : 's') . " and a total amount of {$totalAmount} MAD.";

        if ($role === 'owner' && !empty($order['client_name'])) {
            $answer .= " Client: {$order['client_name']}.";
        }

        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'order_id' => $orderId,
                'status' => $status,
                'total_amount' => $totalAmount,
                'item_count' => $itemCount,
                'client_name' => $role === 'owner' ? ($order['client_name'] ?? null) : null,
                'created_at' => $order['created_at'] ?? null,
                'shown_items' => $shown,
                'shown_this_response' => $shown,
            ],
            $this->itemPreview($items, self::ITEM_LIMIT, $role),
            [
                'has_more' => $itemCount > $shown,
                'order_id' => $orderId,
                'item_count' => $itemCount,
                'shown_items' => $shown,
                'shown_this_response' => $shown,
            ],
            ['Show recent orders', 'Show order summary', 'Open orders page']
        );
    }

    private function itemPreview(array $items, int $limit, string $role): array
    {
        return array_map(function (array $item) use ($role) {
            $base = [
                'product_name' => $item['product_name']
                    ?? $item['component_name']
                    ?? $item['name']
                    ?? null,
                'product_sku' => $item['product_sku']
                    ?? $item['component_sku']
                    ?? $item['sku']
                    ?? null,
                'quantity' => $item['quantity'] ?? null,
                'unit_price' => $item['unit_price'] ?? null,
                'line_total' => $item['line_total'] ?? null,
            ];

            if ($role === 'owner') {
                $base['component_id'] = $item['component_id'] ?? null;
                $base['supplier_id'] = $item['supplier_id'] ?? null;
            }

            return $base;
        }, array_slice($items, 0, $limit));
    }
}