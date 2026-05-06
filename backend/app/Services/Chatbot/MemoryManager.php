<?php

namespace App\Services\Chatbot;

class MemoryManager
{
    private const SESSION_KEY = 'chatbot_context';

    public function get(): array
    {
        return $_SESSION[self::SESSION_KEY] ?? [];
    }

    public function rememberListContext(array $identity, array $toolResult): void
    {
        $role = $identity['role'] ?? 'guest';
        $tool = $toolResult['tool'] ?? null;

        if (!$tool) {
            return;
        }

        $policy = new MemoryPolicy();

        if (!$policy->canRememberTool($role, $tool)) {
            return;
        }

        $data = $toolResult['data'] ?? [];
        $pagination = $data['pagination'] ?? null;
        $filters = $data['filters'] ?? [];

        $page = $pagination['page'] ?? null;
        $totalPages = $pagination['total_pages'] ?? null;

        if (!$page || !$totalPages) {
            return;
        }

        $hasMore = $page < $totalPages;

        $_SESSION[self::SESSION_KEY] = [
            'last_tool' => $tool,
            'last_intent' => $toolResult['meta']['intent'] ?? $tool,
            'last_params' => [
                'page' => $page,
                'limit' => $pagination['limit'] ?? 20,
            ],
            'last_filters' => $filters,
            'last_page' => $page,
            'next_page' => $hasMore ? $page + 1 : null,
            'total_pages' => $totalPages,
            'has_more' => $hasMore,
        ];
    }

    public function clear(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }
}