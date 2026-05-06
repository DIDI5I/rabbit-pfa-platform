<?php

namespace App\Services\Chatbot\Presenters;

class RoleHelpPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'role_help';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';
        $topics = $toolResult['data']['available_topics'] ?? [];

        return $this->success(
            "As a {$role}, you can ask about: " . implode(', ', $topics) . '.',
            $toolResult,
            $role,
            [
                'role' => $role,
                'available_topics_count' => count($topics),
            ],
            [],
            [
                'has_more' => false,
                'total' => count($topics),
                'shown' => count($topics),
            ],
            $toolResult['data']['example_questions'] ?? []
        );
    }
}