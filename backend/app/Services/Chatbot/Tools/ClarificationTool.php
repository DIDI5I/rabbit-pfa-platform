<?php

namespace App\Services\Chatbot\Tools;

use App\Services\Chatbot\ClarificationManager;
use App\Services\Chatbot\PermissionGuard;
use App\Services\Chatbot\ToolRegistry;

class ClarificationTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'clarification_response';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $message = $params['message'] ?? '';

        $manager = new ClarificationManager();
        $resolution = $manager->resolveSelection($message);

        if ($resolution['status'] !== 'matched') {
            return $this->result(
                'clarification_response',
                $resolution['status'],
                [
                    'resolution' => $resolution,
                ],
                $this->meta('clarification_response', $role, 'read_only', false, 0),
                [
                    [
                        'code' => 'clarification_not_matched',
                        'message' => 'Could not match your clarification to one of the listed options.',
                    ],
                ]
            );
        }

        $contextData = $resolution['context'];
        $match = $resolution['match'];

        $originalIntent = $contextData['original_intent'] ?? null;
        $originalParams = $contextData['original_params'] ?? [];
        $entityType = $contextData['entity_type'] ?? null;

        if (!$originalIntent) {
            return $this->result(
                'clarification_response',
                'failed',
                [],
                $this->meta('clarification_response', $role, 'read_only', false, 0),
                [
                    [
                        'code' => 'missing_original_intent',
                        'message' => 'The original clarification intent is missing.',
                    ],
                ]
            );
        }

        $newParams = $originalParams;

        if ($entityType === 'product') {
            $newParams['product_id'] = (int) $match['id'];
            unset($newParams['product_ref']);
        }

        if ($entityType === 'component') {
            $newParams['component_id'] = (int) $match['id'];
            unset($newParams['component_ref']);
        }

        $toolDefinition = (new ToolRegistry())->get($originalIntent);

        if (!$toolDefinition) {
            return $this->result(
                'clarification_response',
                'unsupported',
                [],
                $this->meta('clarification_response', $role, 'read_only', false, 0),
                [
                    [
                        'code' => 'original_tool_not_found',
                        'message' => 'The original tool is no longer available.',
                    ],
                ]
            );
        }

        $permission = (new PermissionGuard())->check(
            $identity,
            $originalIntent,
            $newParams,
            $toolDefinition
        );

        if (!$permission['allowed']) {
            return $this->result(
                $toolDefinition['tool'] ?? $originalIntent,
                $permission['status'],
                [
                    'missing_params' => $permission['missing_params'] ?? [],
                ],
                [
                    'intent' => $originalIntent,
                    'operation_type' => $toolDefinition['operation_type'] ?? 'read_only',
                    'role_scope' => $role,
                    'sensitive' => $toolDefinition['sensitive'] ?? false,
                    'count' => null,
                ],
                [
                    [
                        'code' => $permission['code'],
                        'message' => $permission['reason'],
                    ],
                ]
            );
        }

        $manager->clear();

        return (new \App\Services\Chatbot\ToolExecutor())->execute(
            $originalIntent,
            $newParams,
            $identity,
            $context
        );
    }
}