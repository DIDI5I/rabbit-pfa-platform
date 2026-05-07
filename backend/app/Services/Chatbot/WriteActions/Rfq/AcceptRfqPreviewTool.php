<?php

namespace App\Services\Chatbot\WriteActions\Rfq;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\Chatbot\WriteActions\PendingActionStore;
use App\Services\Chatbot\WriteActions\WriteActionResponseBuilder;
use App\Services\RfqService;

class AcceptRfqPreviewTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'accept_rfq';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $rfqId = (int) ($params['rfq_id'] ?? 0);

        $detailResponse = (new RfqService())->detail($rfqId);
        $rfq = $detailResponse['data'] ?? null;

        if (!is_array($rfq)) {
            return $this->notFound($identity, $rfqId);
        }

        $allowedResponse = (new RfqService())->getAllowedActions($rfqId);
        $allowedActions = $allowedResponse['data']['allowed_actions'] ?? [];

        if (!in_array('accept', $allowedActions, true)) {
            return [
                'message' => 'RFQ action not allowed.',
                'data' => [
                    'answer' => "RFQ #{$rfqId} cannot be accepted in its current state.",
                    'ai_refined' => false,
                    'intent' => 'accept_rfq',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'write_action',
                    'summary' => [
                        'action_required' => false,
                        'confirmation_required' => false,
                        'rfq_id' => $rfqId,
                        'current_status' => $rfq['status'] ?? null,
                        'allowed_actions' => $allowedActions,
                    ],
                    'items_preview' => [],
                    'result_meta' => [
                        'pending_action' => false,
                        'executed' => false,
                    ],
                    'sources' => [
                        [
                            'tool' => 'accept_rfq',
                            'status' => 'action_not_allowed',
                        ],
                    ],
                    'limitations' => [
                        'The RFQ lifecycle does not allow this action for the current status.',
                    ],
                    'suggested_actions' => [
                        'Show RFQ details',
                        'Show RFQ allowed actions',
                    ],
                ],
            ];
        }

        if (empty($rfq['supplier_id']) || empty($rfq['quoted_price'])) {
            return [
                'message' => 'RFQ action not allowed.',
                'data' => [
                    'answer' => "RFQ #{$rfqId} cannot be accepted because it is missing supplier or quote data.",
                    'ai_refined' => false,
                    'intent' => 'accept_rfq',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'write_action',
                    'summary' => [
                        'action_required' => false,
                        'confirmation_required' => false,
                        'rfq_id' => $rfqId,
                        'current_status' => $rfq['status'] ?? null,
                        'supplier_id' => $rfq['supplier_id'] ?? null,
                        'quoted_price' => $rfq['quoted_price'] ?? null,
                        'missing_requirements' => array_values(array_filter([
                            empty($rfq['supplier_id']) ? 'supplier_id' : null,
                            empty($rfq['quoted_price']) ? 'quoted_price' : null,
                        ])),
                    ],
                    'items_preview' => [],
                    'result_meta' => [
                        'pending_action' => false,
                        'executed' => false,
                    ],
                    'sources' => [
                        [
                            'tool' => 'accept_rfq',
                            'status' => 'missing_required_data',
                        ],
                    ],
                    'limitations' => [
                        'Accepting an RFQ requires supplier and quote data because it creates a purchase lot.',
                    ],
                    'suggested_actions' => [
                        'Show RFQ details',
                        'Show RFQ allowed actions',
                    ],
                ],
            ];
        }

        $action = [
            'intent' => 'accept_rfq',
            'tool' => 'accept_rfq',
            'title' => 'Accept RFQ',
            'description' => "I can accept RFQ #{$rfqId}. This will create a purchase lot and notify the supplier.",
            'params' => [
                'rfq_id' => $rfqId,
                'decision_note' => null,
            ],
            'preview' => [
                [
                    'type' => 'rfq_status_change',
                    'rfq_id' => $rfqId,
                    'current_status' => $rfq['status'] ?? null,
                    'new_status' => 'accepted',
                    'supplier_id' => $rfq['supplier_id'] ?? null,
                    'component_id' => $rfq['component_id'] ?? null,
                    'quantity_requested' => $rfq['quantity_requested'] ?? null,
                    'quoted_price' => $rfq['quoted_price'] ?? null,
                    'side_effects' => [
                        'purchase_lot_created',
                        'supplier_notified',
                        'owner_notified_for_purchase_lot_finalization',
                    ],
                ],
            ],
            'risk_level' => 'high',
            'ttl_seconds' => 300,
        ];

        $store = new PendingActionStore();
        $store->put($identity, $action);

        $pending = $store->get($identity);

        return (new WriteActionResponseBuilder())->preview(
            $identity,
            $pending ?? $action
        );
    }

    private function notFound(array $identity, int $rfqId): array
    {
        return [
            'message' => 'RFQ not found.',
            'data' => [
                'answer' => "RFQ #{$rfqId} was not found or is not accessible.",
                'ai_refined' => false,
                'intent' => 'accept_rfq',
                'confidence' => 'high',
                'role' => $identity['role'] ?? 'guest',
                'operation_type' => 'write_action',
                'summary' => [
                    'action_required' => false,
                    'confirmation_required' => false,
                    'rfq_id' => $rfqId,
                ],
                'items_preview' => [],
                'result_meta' => [
                    'pending_action' => false,
                    'executed' => false,
                ],
                'sources' => [
                    [
                        'tool' => 'accept_rfq',
                        'status' => 'not_found_or_not_accessible',
                    ],
                ],
                'limitations' => [],
                'suggested_actions' => [
                    'Show RFQs',
                ],
            ],
        ];
    }
}