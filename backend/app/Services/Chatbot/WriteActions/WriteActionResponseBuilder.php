<?php

namespace App\Services\Chatbot\WriteActions;

class WriteActionResponseBuilder
{
    public function preview(array $identity, array $action): array
    {
        return [
            'message' => 'Chatbot action requires confirmation.',
            'data' => [
                'answer' => $this->previewAnswer($action),
                'ai_refined' => false,

                'intent' => $action['intent'] ?? 'write_action',
                'confidence' => 'high',
                'role' => $identity['role'] ?? 'guest',
                'operation_type' => 'write_action',

                'summary' => [
                    'action_required' => true,
                    'confirmation_required' => true,
                    'risk_level' => $action['risk_level'] ?? 'medium',
                    'expires_in_seconds' => $action['ttl_seconds'] ?? 300,
                ],

                'items_preview' => $action['preview'] ?? [],

                'result_meta' => [
                    'pending_action' => true,
                    'action_id' => $action['action_id'] ?? null,
                    'tool' => $action['tool'] ?? null,
                    'params' => $action['params'] ?? [],
                ],

                'sources' => [
                    [
                        'tool' => $action['tool'] ?? ($action['intent'] ?? 'write_action'),
                        'status' => 'pending_confirmation',
                    ],
                ],

                'limitations' => [
                    'This action has not been executed yet.',
                    'Reply with confirm to execute, or cancel to discard.',
                ],

                'suggested_actions' => [
                    'Confirm',
                    'Cancel',
                ],
            ],
        ];
    }

    public function confirmed(array $identity, array $action, array $result): array
    {
        $executed = (bool) ($result['executed'] ?? false);

        return [
            'message' => $executed
                ? 'Chatbot action executed successfully.'
                : 'Chatbot action could not be executed.',
            'data' => [
                'answer' => $result['answer'] ?? (
                    $executed
                        ? 'Confirmed. The action was executed successfully.'
                        : 'Confirmed, but the action could not be executed.'
                ),
                'ai_refined' => false,

                'intent' => $action['intent'] ?? 'write_action',
                'confidence' => 'high',
                'role' => $identity['role'] ?? 'guest',
                'operation_type' => 'write_action',

                'summary' => $result['summary'] ?? [],
                'items_preview' => $result['items_preview'] ?? [],

                'result_meta' => [
                    'pending_action' => false,
                    'executed' => $executed,
                    'action_id' => $action['action_id'] ?? null,
                    'tool' => $action['tool'] ?? null,
                ],

                'sources' => $result['sources'] ?? [
                    [
                        'tool' => $action['tool'] ?? ($action['intent'] ?? 'write_action'),
                        'status' => $executed ? 'executed' : 'execution_failed',
                    ],
                ],

                'limitations' => $result['limitations'] ?? [],
                'suggested_actions' => $result['suggested_actions'] ?? [],
            ],
        ];
    }
    public function cancelled(array $identity, ?array $action = null): array
    {
        return [
            'message' => 'Chatbot action cancelled.',
            'data' => [
                'answer' => 'Cancelled. No changes were made.',
                'ai_refined' => false,

                'intent' => $action['intent'] ?? 'cancel_write_action',
                'confidence' => 'high',
                'role' => $identity['role'] ?? 'guest',
                'operation_type' => 'write_action',

                'summary' => [
                    'cancelled' => true,
                    'changes_made' => false,
                ],

                'items_preview' => [],

                'result_meta' => [
                    'pending_action' => false,
                    'cancelled' => true,
                    'action_id' => $action['action_id'] ?? null,
                ],

                'sources' => [
                    [
                        'tool' => $action['tool'] ?? 'write_action',
                        'status' => 'cancelled',
                    ],
                ],

                'limitations' => [],
                'suggested_actions' => [],
            ],
        ];
    }

    public function noPendingAction(array $identity, string $intent = 'confirm_write_action'): array
    {
        return [
            'message' => 'No pending chatbot action.',
            'data' => [
                'answer' => 'There is no pending action to confirm or cancel.',
                'ai_refined' => false,

                'intent' => $intent,
                'confidence' => 'high',
                'role' => $identity['role'] ?? 'guest',
                'operation_type' => 'write_action',

                'summary' => [
                    'pending_action' => false,
                ],

                'items_preview' => [],
                'result_meta' => [],
                'sources' => [],
                'limitations' => [
                    'No pending action exists for this session.',
                ],
                'suggested_actions' => [],
            ],
        ];
    }
    private function previewAnswer(array $action): string
    {
        $title = $action['title'] ?? 'this action';
        $description = $action['description'] ?? null;

        if ($description) {
            return "{$description} Reply with confirm to execute, or cancel to discard.";
        }

        return "I can prepare {$title}. Reply with confirm to execute, or cancel to discard.";
    }
}