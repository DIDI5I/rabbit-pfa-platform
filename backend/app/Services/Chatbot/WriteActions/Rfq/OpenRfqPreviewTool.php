<?php

namespace App\Services\Chatbot\WriteActions\Rfq;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\Chatbot\WriteActions\PendingActionStore;
use App\Services\Chatbot\WriteActions\WriteActionResponseBuilder;
use App\Services\RfqService;

class OpenRfqPreviewTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'open_rfq';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $rfqId = (int) ($params['rfq_id'] ?? 0);

        $detailResponse = (new RfqService())->detail($rfqId);
        $rfq = $detailResponse['data'] ?? null;

        if (!is_array($rfq)) {
            return [
                'message' => 'RFQ not found.',
                'data' => [
                    'answer' => "RFQ #{$rfqId} was not found or is not accessible.",
                    'ai_refined' => false,
                    'intent' => 'open_rfq',
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
                            'tool' => 'open_rfq',
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

        $allowedResponse = (new RfqService())->getAllowedActions($rfqId);
        $allowedActions = $allowedResponse['data']['allowed_actions'] ?? [];

        if (!in_array('open', $allowedActions, true)) {
            return [
                'message' => 'RFQ action not allowed.',
                'data' => [
                    'answer' => "RFQ #{$rfqId} cannot be opened in its current state.",
                    'ai_refined' => false,
                    'intent' => 'open_rfq',
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
                            'tool' => 'open_rfq',
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

        if (empty($rfq['supplier_id'])) {
            return [
                'message' => 'RFQ action not allowed.',
                'data' => [
                    'answer' => "RFQ #{$rfqId} cannot be opened because no supplier is assigned.",
                    'ai_refined' => false,
                    'intent' => 'open_rfq',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'write_action',
                    'summary' => [
                        'action_required' => false,
                        'confirmation_required' => false,
                        'rfq_id' => $rfqId,
                        'current_status' => $rfq['status'] ?? null,
                        'supplier_id' => $rfq['supplier_id'] ?? null,
                        'missing_requirements' => [
                            'supplier_id',
                        ],
                    ],
                    'items_preview' => [],
                    'result_meta' => [
                        'pending_action' => false,
                        'executed' => false,
                    ],
                    'sources' => [
                        [
                            'tool' => 'open_rfq',
                            'status' => 'missing_required_data',
                        ],
                    ],
                    'limitations' => [
                        'Opening an RFQ requires an assigned supplier because the supplier must be notified.',
                    ],
                    'suggested_actions' => [
                        'Show RFQ details',
                    ],
                ],
            ];
        }

        $action = [
            'intent' => 'open_rfq',
            'tool' => 'open_rfq',
            'title' => 'Open RFQ',
            'description' => "I can open RFQ #{$rfqId}. This will send it to the supplier and allow quoting.",
            'params' => [
                'rfq_id' => $rfqId,
                'decision_note' => null,
            ],
            'preview' => [
                [
                    'type' => 'rfq_status_change',
                    'rfq_id' => $rfqId,
                    'current_status' => $rfq['status'] ?? null,
                    'new_status' => 'open',
                    'supplier_id' => $rfq['supplier_id'] ?? null,
                    'component_id' => $rfq['component_id'] ?? null,
                    'quantity_requested' => $rfq['quantity_requested'] ?? null,
                    'side_effects' => [
                        'supplier_notified',
                        'supplier_can_quote',
                    ],
                ],
            ],
            'risk_level' => 'medium',
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
}