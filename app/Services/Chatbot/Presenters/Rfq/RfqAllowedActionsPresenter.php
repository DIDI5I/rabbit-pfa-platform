<?php

namespace App\Services\Chatbot\Presenters\Rfq;

use App\Services\Chatbot\Presenters\BaseResponsePresenter;

class RfqAllowedActionsPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'rfq_allowed_actions';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';
        $rfqId = $toolResult['data']['rfq_id'] ?? null;
        $actions = $toolResult['data']['allowed_actions'] ?? [];

        if (!is_array($actions)) {
            $actions = [];
        }

        $count = count($actions);

        $answer = $count === 0
            ? "RFQ {$rfqId} has no allowed actions for your current role."
            : "RFQ {$rfqId} has {$count} allowed action" . ($count === 1 ? '' : 's') . " for your current role.";

        $itemsPreview = array_map(function ($action) {
            return [
                'action' => is_array($action) ? ($action['action'] ?? $action['name'] ?? json_encode($action)) : $action,
                'label' => is_array($action) ? ($action['label'] ?? null) : null,
                'status' => is_array($action) ? ($action['status'] ?? null) : null,
            ];
        }, $actions);

        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'rfq_id' => $rfqId,
                'total_actions' => $count,
            ],
            $itemsPreview,
            [
                'has_more' => false,
                'rfq_id' => $rfqId,
                'total_actions' => $count,
            ],
            [
                "Show RFQ {$rfqId}",
                'Open RFQ page',
            ]
        );
    }
}