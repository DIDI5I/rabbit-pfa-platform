<?php

namespace App\Services\Chatbot\Presenters\Rfq;

use App\Services\Chatbot\Presenters\BaseResponsePresenter;

class RfqsByStatusPresenter extends BaseResponsePresenter
{
    protected const PREVIEW_LIMIT = 5;

    public function supports(string $tool): bool
    {
        return $tool === 'rfqs_by_status';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';
        $status = $toolResult['data']['status'] ?? null;
        $rfqs = $toolResult['data']['rfqs'] ?? [];
        $count = $toolResult['data']['count'] ?? count($rfqs);

        $shown = min(self::PREVIEW_LIMIT, count($rfqs));

        if ($count === 0) {
            $answer = "I found no RFQs with status {$status}.";
        } else {
            $answer = "I found {$count} RFQ" . ($count === 1 ? '' : 's') . " with status {$status}. Showing {$shown}.";
        }

        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'status' => $status,
                'total' => $count,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $this->previewRfqs($rfqs, self::PREVIEW_LIMIT),
            [
                'has_more' => $count > $shown,
                'status' => $status,
                'total' => $count,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $count > $shown
                ? ['Ask to see more RFQs', 'Open RFQ page']
                : ['Open RFQ page']
        );
    }

    private function previewRfqs(array $rfqs, int $limit): array
    {
        return array_map(function (array $rfq) {
            return [
                'id' => $rfq['id'] ?? null,
                'status' => $rfq['status'] ?? null,
                'product_name' => $rfq['component_name'] ?? $rfq['product_name'] ?? null,
                'product_sku' => $rfq['component_sku'] ?? $rfq['product_sku'] ?? null,
                'supplier_name' => $rfq['supplier_name'] ?? null,
                'quantity_requested' => $rfq['quantity_requested'] ?? null,
                'quoted_price' => $rfq['quoted_price'] ?? null,
                'created_at' => $rfq['created_at'] ?? null,
            ];
        }, array_slice($rfqs, 0, $limit));
    }
}