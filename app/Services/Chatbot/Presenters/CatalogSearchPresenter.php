<?php

namespace App\Services\Chatbot\Presenters;

class CatalogSearchPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'catalog_search';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $products = $toolResult['data']['products'] ?? [];
        $count = $toolResult['data']['count'] ?? count($products);
        $total = $toolResult['data']['total'] ?? $count;
        $pagination = $toolResult['data']['pagination'] ?? null;
        $filters = $toolResult['data']['filters'] ?? [];

        $shown = min(self::PREVIEW_LIMIT, count($products));

        $page = is_array($pagination) && isset($pagination['page'])
            ? (int) $pagination['page']
            : 1;

        $limit = is_array($pagination) && isset($pagination['limit'])
            ? (int) $pagination['limit']
            : self::PREVIEW_LIMIT;

        $shownTotal = min($total, (($page - 1) * $limit) + $shown);

        $hasMore = false;

        if (
            is_array($pagination)
            && isset($pagination['page'])
            && isset($pagination['total_pages'])
        ) {
            $hasMore = (int) $pagination['page'] < (int) $pagination['total_pages'];
        } else {
            $hasMore = $total > $shown;
        }

        if ($count === 0) {
            $answer = 'I could not find any catalogue products.';
        } elseif ($page > 1) {
            $answer = "Here are {$shown} more catalogue product(s). {$shownTotal} of {$total} shown so far.";
        } elseif ($total > $shown) {
            $answer = "I found {$total} catalogue product(s). Showing the first {$shown}.";
        } else {
            $answer = "I found {$total} catalogue product(s).";
        }

        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'total' => $total,
                'shown' => $shownTotal,
                'shown_this_response' => $shown,
            ],
            $this->preview($products, [
                'id',
                'name',
                'sku',
                'category',
                'availability_status',
            ]),
            $this->listMeta($total, $shown, $pagination, $filters),
            $hasMore
                ? ['Ask to see more results', 'Open catalogue']
                : ['Open catalogue']
        );
    }
}