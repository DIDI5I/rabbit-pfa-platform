<?php

namespace App\Services\Chatbot\Tools;

use App\Services\Chatbot\NavigationResolver;

class NavigationTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'navigate';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';

        $resolved = (new NavigationResolver())->resolve(
            $params['target'] ?? '',
            $role,
            $context['previous_page_link'] ?? null
        );

        return $this->result('navigate', $resolved['status'], $resolved['data'], [
            'intent' => 'navigate',
            'operation_type' => 'navigation',
            'role_scope' => $role,
            'sensitive' => $resolved['status'] === 'success'
                && str_starts_with($resolved['data']['navigation']['target_key'] ?? '', 'owner_'),
            'count' => null,
        ], $resolved['errors'] ?? []);
    }
}