<?php

namespace App\Services\Chatbot\Presenters;

class OwnerProductDependenciesPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'owner_product_dependencies';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $dependencies = $toolResult['data']['dependencies'] ?? [];
        $productId = $toolResult['data']['product_id'] ?? null;
        $count = $toolResult['data']['count'] ?? count($dependencies);

        $shown = min(self::PREVIEW_LIMIT, count($dependencies));
        $resolvedItem = $toolResult['data']['resolved_item'] ?? [];
        $productName = $resolvedItem['name'] ?? "Product {$productId}";
        $productSku = $resolvedItem['sku'] ?? null;

        $productLabel = $productSku
            ? "{$productName} ({$productSku})"
            : $productName;

        $answer = $count === 0
        ? "{$productLabel} has no registered dependencies."
        : "{$productLabel} has {$count} registered dependencies. Showing {$shown}.";
        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'product_id' => $productId,
                'total_dependencies' => $count,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $this->preview($dependencies),
            [
                'has_more' => $count > $shown,
                'total' => $count,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $count > $shown
                ? ['Ask to see more dependencies', 'Ask for cost rollup', 'Open owner product page']
                : ['Ask for cost rollup', 'Open owner product page']
        );
    }
}