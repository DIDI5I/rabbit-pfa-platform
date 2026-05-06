<?php

namespace App\Services\Chatbot;

class PermissionGuard
{
    public function check(array $identity, string $intent, array $params, array $toolDefinition): array
    {
        $role = $identity['role'] ?? 'guest';
        $allowedRoles = $toolDefinition['roles'] ?? [];

        if (!in_array($role, $allowedRoles, true)) {
            return [
                'allowed' => false,
                'status' => 'permission_denied',
                'reason' => 'This request is not available for your role.',
                'code' => 'role_not_allowed',
            ];
        }

        foreach (($toolDefinition['required_params'] ?? []) as $param) {
            if (!$this->hasParam($params, $param)) {
                return [
                    'allowed' => false,
                    'status' => 'missing_params',
                    'reason' => "{$param} is required.",
                    'code' => 'missing_' . $param,
                    'missing_params' => [$param],
                ];
            }
        }

        $requiredAny = $toolDefinition['required_params_any'] ?? [];

        if (!empty($requiredAny)) {
            $hasAny = false;

            foreach ($requiredAny as $param) {
                if ($this->hasParam($params, $param)) {
                    $hasAny = true;
                    break;
                }
            }

            if (!$hasAny) {
                return [
                    'allowed' => false,
                    'status' => 'missing_params',
                    'reason' => 'One of these parameters is required: ' . implode(', ', $requiredAny) . '.',
                    'code' => 'missing_any_required_param',
                    'missing_params' => $requiredAny,
                ];
            }
        }

        return [
            'allowed' => true,
            'status' => 'allowed',
            'reason' => null,
            'code' => null,
        ];
    }

    private function hasParam(array $params, string $param): bool
    {
        return array_key_exists($param, $params)
            && $params[$param] !== null
            && $params[$param] !== '';
    }
}