<?php

namespace App\Services\Chatbot\Presenters;

class ShowMorePresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'show_more';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        if (($toolResult['status'] ?? null) === 'empty') {
            return $this->response('No more results available.', [
                'answer' => $toolResult['data']['message'] ?? 'There are no more results to show.',
                'intent' => 'show_more',
                'confidence' => 'high',
                'role' => $role,
                'operation_type' => 'read_only',
                'summary' => [
                    'has_more' => false,
                ],
                'items_preview' => [],
                'result_meta' => [
                    'has_more' => false,
                    'total' => 0,
                    'shown' => 0,
                ],
                'sources' => [
                    [
                        'tool' => 'show_more',
                        'status' => 'used',
                    ],
                ],
                'limitations' => [],
                'suggested_actions' => [
                    'Start a new search',
                    'Open catalogue',
                ],
            ]);
        }

        return $this->success(
            'I found more results.',
            $toolResult,
            $role,
            [],
            [],
            [
                'has_more' => false,
            ],
            []
        );
    }
}