<?php

namespace App\Services\Chatbot\Presenters;

class PurchaseLotsByProductPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'purchase_lots_by_product';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $lots = $toolResult['data']['purchase_lots'] ?? [];
        $productId = $toolResult['data']['product_id'] ?? null;
        $count = $toolResult['data']['count'] ?? count($lots);
        $shown = min(self::PREVIEW_LIMIT, count($lots));
        $productName = $lots[0]['component_name'] ?? "product {$productId}";
        $productSku = $lots[0]['component_sku'] ?? null;

        $productLabel = $productSku
            ? "{$productName} ({$productSku})"
            : $productName;
        $answer = $count === 0
? "I found no purchase lots for {$productLabel}."
: "I found {$count} purchase lot" . ($count === 1 ? '' : 's') . " for {$productLabel}. Showing {$shown}.";
        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'product_id' => $productId,
                'product_name' => $productName,
                'product_sku' => $productSku,
                'total_lots' => $count,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $this->preview($lots, [
                'id',
                'component_name',
                'component_sku',
                'supplier_name',
                'quantity_received',
                'unit_purchase_cost',
                'total_purchase_cost',
                'status',
                'purchase_date',
            ]),
            [
                'has_more' => $count > $shown,
                'total' => $count,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $count > $shown
                ? ['Ask to see more purchase lots', 'Open purchase lots page']
                : ['Open purchase lots page']
        );
    }
}