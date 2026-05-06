<?php

namespace App\Services\Chatbot\Presenters;

class NavigationPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'navigate';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        if (($toolResult['status'] ?? null) !== 'success') {
            return $this->response('Navigation not allowed.', [
                'answer' => 'You do not have permission to open that page.',
                'intent' => $toolResult['meta']['intent'] ?? 'navigate',
                'confidence' => 'high',
                'role' => $role,
                'operation_type' => 'navigation',
                'summary' => [],
                'items_preview' => [],
                'result_meta' => [],
                'sources' => [
                    [
                        'tool' => 'navigate',
                        'status' => 'denied',
                    ],
                ],
                'limitations' => array_column($toolResult['errors'] ?? [], 'message'),
                'suggested_actions' => $this->suggestedActions($role),
            ]);
        }

        $navigation = $toolResult['data']['navigation'] ?? [];

        return $this->response('Navigation target resolved.', [
            'answer' => 'Opening the requested page.',
            'intent' => $toolResult['meta']['intent'] ?? 'navigate',
            'confidence' => 'high',
            'role' => $role,
            'operation_type' => 'navigation',
            'navigation' => $navigation,
            'summary' => [
                'target_page' => $navigation['target_page'] ?? null,
            ],
            'items_preview' => [],
            'result_meta' => [
                'has_more' => false,
            ],
            'sources' => [
                [
                    'tool' => 'navigate',
                    'status' => 'used',
                ],
            ],
            'limitations' => [],
            'suggested_actions' => [],
        ]);
    }
}