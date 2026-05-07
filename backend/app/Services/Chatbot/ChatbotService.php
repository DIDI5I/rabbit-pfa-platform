<?php

namespace App\Services\Chatbot;

use App\Services\Chatbot\Ai\AiAnswerRefiner;

use App\Services\Chatbot\WriteActions\PendingActionStore;
use App\Services\Chatbot\WriteActions\WriteActionExecutor;
use App\Services\Chatbot\WriteActions\WriteActionResponseBuilder;

class ChatbotService
{
    public function handle(string $message, array $context = []): array
    {
        $identity = (new IdentityResolver())->resolve();

        $classifier = new IntentClassifier();
        $classification = $classifier->classify($message);

        $intent = $classification['intent'];
        $params = $classification['params'] ?? [];

        $registry = new ToolRegistry();
        $toolDefinition = $registry->get($intent);

        $operationType = (new OperationClassifier())->classify($intent, $toolDefinition);

        $logger = new ChatbotLogger();

        if ($operationType === 'unsupported') {
            $logger->log([
                'user_id' => $identity['user_id'] ?? null,
                'role' => $identity['role'] ?? 'guest',
                'message' => $message,
                'intent' => $intent,
                'tool' => null,
                'status' => 'unsupported',
                'operation_type' => 'unsupported',
                'permission_status' => null,
                'error' => 'No valid intent matched the request.',
            ]);

            return $this->finalizeResponse(
                $this->fail($identity, $intent, 'No valid intent matched the request.'),
                $identity,
                $logger,
                [
                    'message' => $message,
                    'intent' => $intent,
                    'tool' => null,
                    'operation_type' => 'unsupported',
                    'permission_status' => null,
                ]
            );
        }

        if (in_array($intent, ['confirm_write_action', 'cancel_write_action'], true)) {
            return $this->handlePendingWriteActionIntent($intent, $identity);
        }

        $guard = new PermissionGuard();
        $permission = $guard->check($identity, $intent, $params, $toolDefinition);

        if (!$permission['allowed']) {
            $toolResult = [
                'tool' => $toolDefinition['tool'] ?? $intent,
                'status' => $permission['status'],
                'data' => [
                    'missing_params' => $permission['missing_params'] ?? [],
                ],
                'meta' => [
                    'intent' => $intent,
                    'operation_type' => $operationType,
                    'role_scope' => $identity['role'] ?? 'guest',
                    'sensitive' => $toolDefinition['sensitive'] ?? false,
                    'count' => null,
                ],
                'errors' => [
                    [
                        'code' => $permission['code'],
                        'message' => $permission['reason'],
                    ],
                ],
            ];

            $logger->log([
                'user_id' => $identity['user_id'] ?? null,
                'role' => $identity['role'] ?? 'guest',
                'message' => $message,
                'intent' => $intent,
                'tool' => $toolDefinition['tool'] ?? $intent,
                'status' => $permission['status'],
                'operation_type' => $operationType,
                'permission_status' => 'denied',
                'error' => $permission['reason'],
            ]);

            $response = (new AnswerComposer())->compose($toolResult, $identity);

            return $this->finalizeResponse(
                $response,
                $identity,
                $logger,
                [
                    'message' => $message,
                    'intent' => $intent,
                    'tool' => $toolDefinition['tool'] ?? $intent,
                    'operation_type' => $operationType,
                    'permission_status' => 'denied',
                ]
            );

        }

        if (in_array($intent, [
                'mark_all_notifications_read',
                'mark_notification_read',
                'reject_rfq',
                'accept_rfq',
                'expire_rfq',
                'open_rfq',
                'update_order_status',
                'record_stock_movement',
                'set_stock_level',
                'finalize_purchase_lot',
                'update_pending_purchase_lot_costs',
            ], true)) {
            
            $toolResult = (new ToolExecutor())->execute($intent, $params, $identity, $context);

            $logger->log([
                'user_id' => $identity['user_id'] ?? null,
                'role' => $identity['role'] ?? 'guest',
                'message' => $message,
                'intent' => $intent,
                'tool' => $toolResult['tool'] ?? ($toolDefinition['tool'] ?? $intent),
                'status' => $toolResult['status'] ?? 'pending_confirmation',
                'operation_type' => 'write_action',
                'permission_status' => 'pending_confirmation',
                'error' => !empty($toolResult['errors'])
                    ? json_encode($toolResult['errors'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : null,
            ]);

            return $this->finalizeResponse(
                $toolResult,
                $identity,
                $logger,
                [
                    'message' => $message,
                    'intent' => $intent,
                    'tool' => $toolResult['tool'] ?? ($toolDefinition['tool'] ?? $intent),
                    'operation_type' => 'write_action',
                    'permission_status' => 'pending_confirmation',
                ]
            );
        }

        if ($operationType === 'write_action') {
            $logger->log([
                'user_id' => $identity['user_id'] ?? null,
                'role' => $identity['role'] ?? 'guest',
                'message' => $message,
                'intent' => $intent,
                'tool' => $toolDefinition['tool'] ?? $intent,
                'status' => 'blocked_write_action',
                'operation_type' => 'write_action',
                'permission_status' => 'blocked',
                'error' => 'Write action has no confirmation preview handler.',
            ]);

            $response = [
                'message' => 'Chatbot action not supported yet.',
                'data' => [
                    'answer' => 'I can retrieve and explain information, but this write action is not supported yet.',
                    'ai_refined' => false,
                    'intent' => $intent,
                    'confidence' => $classification['confidence'] ?? 'medium',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => 'write_action',
                    'summary' => [],
                    'items_preview' => [],
                    'result_meta' => [],
                    'sources' => [],
                    'limitations' => [
                        'This write action does not have a confirmation preview handler yet.',
                    ],
                    'suggested_actions' => [],
                ],
            ];

            return $this->finalizeResponse(
                $response,
                $identity,
                $logger,
                [
                    'message' => $message,
                    'intent' => $intent,
                    'tool' => $toolDefinition['tool'] ?? $intent,
                    'operation_type' => 'write_action',
                    'permission_status' => 'blocked',
                ]
            );
        }

        $toolResult = (new ToolExecutor())->execute($intent, $params, $identity, $context);

        $logger->log([
            'user_id' => $identity['user_id'] ?? null,
            'role' => $identity['role'] ?? 'guest',
            'message' => $message,
            'intent' => $intent,
            'tool' => $toolResult['tool'] ?? ($toolDefinition['tool'] ?? $intent),
            'status' => $toolResult['status'] ?? null,
            'operation_type' => $operationType,
            'permission_status' => 'allowed',
            'error' => !empty($toolResult['errors'])
                ? json_encode($toolResult['errors'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
        ]);

        (new MemoryManager())->rememberListContext($identity, $toolResult);

        $response = (new AnswerComposer())->compose($toolResult, $identity);

        return $this->finalizeResponse(
            $response,
            $identity,
            $logger,
            [
                'message' => $message,
                'intent' => $intent,
                'tool' => $toolResult['tool'] ?? ($toolDefinition['tool'] ?? $intent),
                'operation_type' => $operationType,
                'permission_status' => 'allowed',
            ]
        );
    }
    private function fail(array $identity, string $intent, string $reason): array
    {
        return [
            'message' => 'Chatbot could not answer this request.',
            'data' => [
                'answer' => 'I could not understand that request safely. Please try asking in a more specific way.',
                'intent' => $intent,
                'confidence' => 'none',
                'role' => $identity['role'] ?? 'guest',
                'operation_type' => 'unsupported',
                'sources' => [],
                'limitations' => [
                    $reason,
                ],
                'suggested_actions' => (new RoleProfileRegistry())->get($identity['role'] ?? 'guest')['suggested_actions'] ?? [],
            ],
        ];
    }

    private function finalizeResponse(
        array $response,
        array $identity,
        ?ChatbotLogger $logger = null,
        array $logContext = []
    ): array {
        $response = (new AiAnswerRefiner())->refine($response);

        $verification = (new SecurityVerifier())->verify($response, $identity);

        if (!$verification['passed']) {
            if ($logger !== null) {
                $logger->log([
                    'user_id' => $identity['user_id'] ?? null,
                    'role' => $identity['role'] ?? 'guest',
                    'message' => $logContext['message'] ?? null,
                    'intent' => $logContext['intent'] ?? ($response['data']['intent'] ?? 'unknown'),
                    'tool' => $logContext['tool'] ?? null,
                    'status' => 'security_verification_failed',
                    'operation_type' => $logContext['operation_type'] ?? ($response['data']['operation_type'] ?? 'unknown'),
                    'permission_status' => $logContext['permission_status'] ?? null,
                    'error' => $verification['reason'],
                ]);
            }

            return [
                'message' => 'Chatbot could not answer this request safely.',
                'data' => [
                    'answer' => 'I cannot provide that information with your current access level.',
                    'ai_refined' => false,
                    'intent' => $response['data']['intent'] ?? 'unknown',
                    'confidence' => 'high',
                    'role' => $identity['role'] ?? 'guest',
                    'operation_type' => $response['data']['operation_type'] ?? 'unknown',
                    'summary' => [],
                    'items_preview' => [],
                    'result_meta' => [],
                    'sources' => [],
                    'limitations' => [
                        $verification['reason'],
                    ],
                    'suggested_actions' => [],
                ],
            ];
        }

        return $response;
        }

    private function handlePendingWriteActionIntent(string $intent, array $identity): array
    {
        $store = new PendingActionStore();
        $builder = new WriteActionResponseBuilder();

        $action = $store->get($identity);

        if (!$action) {
            return $this->finalizeResponse(
                $builder->noPendingAction($identity, $intent),
                $identity
            );
        }

        if ($intent === 'cancel_write_action') {
            (new \App\Services\Chatbot\WriteActions\WriteActionLogger())->log([
                'user_id' => $identity['user_id'] ?? null,
                'role' => $identity['role'] ?? 'guest',
                'intent' => $action['intent'] ?? null,
                'tool' => $action['tool'] ?? null,
                'action_id' => $action['action_id'] ?? null,
                'status' => 'cancelled',
                'risk_level' => $action['risk_level'] ?? null,
                'confirmed' => false,
                'executed' => false,
                'cancelled' => true,
                'params_summary' => $action['params'] ?? [],
            ]);

            $store->clear();

            return $this->finalizeResponse(
                $builder->cancelled($identity, $action),
                $identity
            );
        }

        (new \App\Services\Chatbot\WriteActions\WriteActionLogger())->log([
            'user_id' => $identity['user_id'] ?? null,
            'role' => $identity['role'] ?? 'guest',
            'intent' => $action['intent'] ?? null,
            'tool' => $action['tool'] ?? null,
            'action_id' => $action['action_id'] ?? null,
            'status' => 'confirmed',
            'risk_level' => $action['risk_level'] ?? null,
            'confirmed' => true,
            'executed' => false,
            'cancelled' => false,
            'params_summary' => $action['params'] ?? [],
        ]);

        $result = (new WriteActionExecutor())->execute($identity, $action);

        (new \App\Services\Chatbot\WriteActions\WriteActionLogger())->log([
            'user_id' => $identity['user_id'] ?? null,
            'role' => $identity['role'] ?? 'guest',
            'intent' => $action['intent'] ?? null,
            'tool' => $action['tool'] ?? null,
            'action_id' => $action['action_id'] ?? null,
            'status' => ($result['executed'] ?? false) ? 'executed' : 'execution_failed',
            'risk_level' => $action['risk_level'] ?? null,
            'confirmed' => true,
            'executed' => (bool) ($result['executed'] ?? false),
            'cancelled' => false,
            'params_summary' => $action['params'] ?? [],
            'error' => ($result['executed'] ?? false) ? null : ($result['answer'] ?? 'Execution failed'),
        ]);

        $store->clear();

        return $this->finalizeResponse(
            $builder->confirmed($identity, $action, $result),
            $identity
        );
    }
}