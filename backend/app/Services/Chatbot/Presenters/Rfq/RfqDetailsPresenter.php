<?php

namespace App\Services\Chatbot\Presenters\Rfq;

use App\Services\Chatbot\Presenters\BaseResponsePresenter;

class RfqDetailsPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'rfq_details';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';
        $rfq = $toolResult['data']['rfq'] ?? [];
        $rfqId = $toolResult['data']['rfq_id'] ?? ($rfq['id'] ?? null);

        if (($toolResult['status'] ?? null) !== 'success' || empty($rfq)) {
            return $this->success(
                "I could not find RFQ {$rfqId}, or it is not available for your role.",
                $toolResult,
                $role,
                [
                    'rfq_id' => $rfqId,
                    'found' => false,
                ],
                [],
                [
                    'has_more' => false,
                    'rfq_id' => $rfqId,
                ],
                ['Open RFQ page']
            );
        }

        $status = $rfq['status'] ?? 'unknown';
        $productName = $rfq['component_name']
            ?? $rfq['product_name']
            ?? $rfq['name']
            ?? 'the requested product';

        $supplierName = $rfq['supplier_name'] ?? null;

        $answer = "RFQ {$rfqId} is currently {$status} for {$productName}.";

        if ($supplierName) {
            $answer .= " Supplier: {$supplierName}.";
        }

        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'rfq_id' => $rfqId,
                'status' => $status,
                'supplier_name' => $supplierName,
                'quantity_requested' => $rfq['quantity_requested'] ?? null,
                'quoted_price' => $rfq['quoted_price'] ?? null,
                'lead_time_days' => $rfq['lead_time_days'] ?? null,
            ],
            [
                $this->previewRfq($rfq),
            ],
            [
                'has_more' => false,
                'rfq_id' => $rfqId,
                'status' => $status,
            ],
            [
                "Show allowed actions for RFQ {$rfqId}",
                'Open RFQ page',
            ]
        );
    }

    private function previewRfq(array $rfq): array
    {
        return [
            'id' => $rfq['id'] ?? null,
            'status' => $rfq['status'] ?? null,
            'product_name' => $rfq['component_name'] ?? $rfq['product_name'] ?? null,
            'product_sku' => $rfq['component_sku'] ?? $rfq['product_sku'] ?? null,
            'supplier_name' => $rfq['supplier_name'] ?? null,
            'quantity_requested' => $rfq['quantity_requested'] ?? null,
            'quoted_price' => $rfq['quoted_price'] ?? null,
            'lead_time_days' => $rfq['lead_time_days'] ?? null,
            'created_at' => $rfq['created_at'] ?? null,
        ];
    }
}