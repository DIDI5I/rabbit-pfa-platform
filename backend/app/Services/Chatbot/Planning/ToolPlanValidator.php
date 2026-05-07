<?php

namespace App\Services\Chatbot\Planning;

use App\Services\Chatbot\ToolRegistry;
use App\Services\Chatbot\PermissionGuard;

class ToolPlanValidator
{
    private const MAX_TOOLS = 5;

    public function validate(array $classification, array $identity): array
    {
        if (($classification['intent'] ?? null) !== 'multi_source_read') {
            return $this->invalid('invalid_plan_intent');
        }

        if (($classification['operation_type'] ?? null) !== 'read_only') {
            return $this->invalid('multi_source_must_be_read_only');
        }

        $tools = $classification['tools'] ?? [];

        if (!is_array($tools) || count($tools) === 0) {
            return $this->invalid('empty_tool_plan');
        }

        if (count($tools) > self::MAX_TOOLS) {
            return $this->invalid('too_many_tools', [
                'max_tools' => self::MAX_TOOLS,
                'tool_count' => count($tools),
            ]);
        }

        $registry = new ToolRegistry();
        $permissionGuard = new PermissionGuard();

        $validatedTools = [];

        foreach ($tools as $index => $toolPlan) {
            if (!is_array($toolPlan)) {
                return $this->invalid('invalid_tool_plan_item', [
                    'index' => $index,
                ]);
            }

            $intent = trim((string) ($toolPlan['intent'] ?? ''));
            $params = $toolPlan['params'] ?? [];

            if ($intent === '') {
                return $this->invalid('missing_tool_intent', [
                    'index' => $index,
                ]);
            }

            if (!is_array($params)) {
                return $this->invalid('tool_params_must_be_object', [
                    'intent' => $intent,
                    'index' => $index,
                ]);
            }

            $definition = $registry->get($intent);

            if (!is_array($definition)) {
                return $this->invalid('tool_not_registered', [
                    'intent' => $intent,
                    'index' => $index,
                ]);
            }

            if (($definition['operation_type'] ?? null) !== 'read_only') {
                return $this->invalid('multi_source_cannot_use_write_tool', [
                    'intent' => $intent,
                    'operation_type' => $definition['operation_type'] ?? null,
                ]);
            }

            $missingParam = $this->missingRequiredParam($definition, $params);

            if ($missingParam !== null) {
                return $this->invalid('missing_required_param_' . $missingParam, [
                    'intent' => $intent,
                    'required_param' => $missingParam,
                ]);
            }

            $permission = $permissionGuard->check(
                $identity,
                $intent,
                $params,
                $definition
            );

            if (!($permission['allowed'] ?? false)) {
                return $this->invalid('permission_denied', [
                    'intent' => $intent,
                    'reason' => $permission['reason'] ?? null,
                ]);
            }

            $validatedTools[] = [
                'intent' => $intent,
                'params' => $this->sanitizeParams($params),
                'definition' => $definition,
            ];
        }

        return [
            'valid' => true,
            'plan' => [
                'intent' => 'multi_source_read',
                'operation_type' => 'read_only',
                'confidence' => $classification['confidence'] ?? 'medium',
                'tools' => $validatedTools,
                'reason' => $classification['ai_router']['reason']
                    ?? $classification['reason']
                    ?? null,
            ],
        ];
    }

    private function missingRequiredParam(array $definition, array $params): ?string
    {
        foreach (($definition['required_params'] ?? []) as $param) {
            if (
                !array_key_exists($param, $params)
                || $params[$param] === null
                || $params[$param] === ''
            ) {
                return $param;
            }
        }

        return null;
    }

    private function sanitizeParams(array $params): array
    {
        $clean = [];

        foreach ($params as $key => $value) {
            $key = trim((string) $key);

            if ($key === '') {
                continue;
            }

            if (is_string($value)) {
                $value = trim($value);
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    private function invalid(string $reason, array $extra = []): array
    {
        return [
            'valid' => false,
            'reason' => $reason,
            'meta' => $extra,
        ];
    }
}