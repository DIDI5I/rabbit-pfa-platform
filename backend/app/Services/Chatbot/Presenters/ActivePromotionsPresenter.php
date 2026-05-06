<?php

namespace App\Services\Chatbot\Presenters;

class ActivePromotionsPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'active_promotions';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $promotions = $toolResult['data']['promotions'] ?? [];
        $count = $toolResult['data']['count'] ?? count($promotions);
        $total = $toolResult['data']['total'] ?? $count;
        $pagination = $toolResult['data']['pagination'] ?? null;

        $shown = min(self::PREVIEW_LIMIT, count($promotions));

        if ($count === 0) {
            $answer = 'There are no active promotions right now.';
        } elseif ($total > $shown) {
            $answer = "There are {$total} active promotion(s). Showing the first {$shown}.";
        } else {
            $answer = "There are {$total} active promotion(s).";
        }

        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'total' => $total,
                'shown' => $shown,
            ],
            $this->preview($promotions, [
                'id',
                'title',
                'discount_type',
                'discount_value',
                'starts_at',
                'ends_at',
                'status',
            ]),
            $this->listMeta($total, $shown, $pagination, [
                'status' => 'ACTIVE',
            ]),
            $total > $shown
                ? ['Ask to see more promotions', 'Open promotions page']
                : ['Open promotions page']
        );
    }
}