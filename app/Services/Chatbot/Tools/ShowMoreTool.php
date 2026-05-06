<?php

namespace App\Services\Chatbot\Tools;

use App\Services\Chatbot\MemoryManager;

class ShowMoreTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'show_more';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $memory = (new MemoryManager())->get();

        if (empty($memory) || empty($memory['has_more'])) {
            return $this->result('show_more', 'empty', [
                'message' => 'There are no more remembered results to show.',
            ], $this->meta('show_more', $role, 'read_only', false, 0));
        }

        $lastTool = $memory['last_tool'] ?? null;
        $nextPage = $memory['next_page'] ?? null;
        $filters = $memory['last_filters'] ?? [];
        $limit = $memory['last_params']['limit'] ?? 5;

        if (!$lastTool || !$nextPage) {
            return $this->result('show_more', 'empty', [
                'message' => 'There is no next page available.',
            ], $this->meta('show_more', $role, 'read_only', false, 0));
        }

        $nextParams = array_merge($filters, [
            'page' => $nextPage,
            'limit' => $limit,
        ]);

        return match ($lastTool) {
            'catalog_search' => (new CatalogSearchTool())->execute('catalog_search', $nextParams, $identity, $context),

            default => $this->result('show_more', 'unsupported', [
                'last_tool' => $lastTool,
            ], $this->meta('show_more', $role, 'read_only', false), [
                [
                    'code' => 'show_more_not_supported',
                    'message' => 'Show more is not supported for the previous result.',
                ],
            ]),
        };
    }
}