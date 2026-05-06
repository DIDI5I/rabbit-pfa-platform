<?php

namespace App\Services\Chatbot\Tools;

use App\Services\Chatbot\RoleProfileRegistry;

class RoleHelpTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'role_help';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $profile = (new RoleProfileRegistry())->get($role);

        return $this->result('role_help', 'success', [
            'role' => $role,
            'available_topics' => $profile['available_topics'],
            'restricted_topics' => $profile['restricted_topics'],
            'example_questions' => $profile['suggested_actions'],
        ], $this->meta('role_help', $role, 'read_only', false, count($profile['available_topics'])));
    }
}