<?php

namespace App\Services\Chatbot\Presenters;

class ClarificationPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'clarification_response';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        return $this->response('Chatbot could not match the clarification.', [
            'answer' => 'I could not match your reply to one of the listed options. Please reply with the exact ID or SKU from the list.',
            'intent' => $toolResult['meta']['intent'] ?? 'clarification_response',
            'confidence' => 'medium',
            'role' => $role,
            'operation_type' => 'read_only',
            'summary' => [],
            'items_preview' => $toolResult['data']['resolution']['context']['matches'] ?? [],
            'result_meta' => [
                'has_more' => false,
            ],
            'sources' => [
                [
                    'tool' => 'clarification_response',
                    'status' => $toolResult['status'] ?? 'not_matched',
                ],
            ],
            'limitations' => array_column($toolResult['errors'] ?? [], 'message'),
            'suggested_actions' => [
                'Reply with the exact product ID',
                'Reply with the exact SKU',
            ],
        ]);
    }
}