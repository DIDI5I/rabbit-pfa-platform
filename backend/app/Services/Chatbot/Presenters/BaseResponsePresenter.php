<?php

namespace App\Services\Chatbot\Presenters;

use App\Services\Chatbot\RoleProfileRegistry;

abstract class BaseResponsePresenter implements ResponsePresenterInterface
{
    protected const PREVIEW_LIMIT = 5;

    protected function response(string $message, array $data): array
    {
        return [
            'message' => $message,
            'data' => $data,
        ];
    }

    protected function success(
        string $answer,
        array $toolResult,
        string $role,
        array $summary = [],
        array $itemsPreview = [],
        array $resultMeta = [],
        array $suggestedActions = []
    ): array {
        return $this->response('Chatbot answer generated successfully.', [
            'answer' => $answer,
            'intent' => $toolResult['meta']['intent'] ?? $toolResult['tool'],
            'confidence' => 'high',
            'role' => $role,
            'operation_type' => $toolResult['meta']['operation_type'] ?? 'read_only',
            'summary' => $summary,
            'items_preview' => $itemsPreview,
            'result_meta' => $resultMeta,
            'sources' => [
                [
                    'tool' => $toolResult['tool'],
                    'status' => 'used',
                ],
            ],
            'limitations' => [],
            'suggested_actions' => $suggestedActions,
        ]);
    }

    protected function preview(array $items, ?array $fields = null): array
    {
        $limited = array_slice($items, 0, self::PREVIEW_LIMIT);

        if ($fields === null) {
            return $limited;
        }

        return array_map(function (array $item) use ($fields) {
            $preview = [];

            foreach ($fields as $field) {
                if (array_key_exists($field, $item)) {
                    $preview[$field] = $item[$field];
                }
            }

            return $preview;
        }, $limited);
    }

    protected function listMeta(int $total, int $shownThisResponse, ?array $pagination = null, array $filters = []): array
    {
        $page = is_array($pagination) ? ($pagination['page'] ?? null) : null;
        $limit = is_array($pagination) ? ($pagination['limit'] ?? null) : null;
        $totalPages = is_array($pagination) ? ($pagination['total_pages'] ?? null) : null;

        $hasMore = $page !== null && $totalPages !== null
            ? (int) $page < (int) $totalPages
            : $total > $shownThisResponse;

        $shownTotal = $page !== null && $limit !== null
            ? min($total, (((int) $page - 1) * (int) $limit) + $shownThisResponse)
            : $shownThisResponse;

        return [
            'has_more' => $hasMore,
            'total' => $total,
            'shown' => $shownTotal,
            'shown_this_response' => $shownThisResponse,
            'page' => $page !== null ? (int) $page : null,
            'limit' => $limit !== null ? (int) $limit : null,
            'total_pages' => $totalPages !== null ? (int) $totalPages : null,
            'filters' => $filters,
        ];
    }

    protected function suggestedActions(string $role): array
    {
        return (new RoleProfileRegistry())->get($role)['suggested_actions'] ?? [];
    }
}