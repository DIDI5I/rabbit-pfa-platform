<?php

namespace App\Services\Chatbot\Presenters;

class StockMovementsByComponentPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'stock_movements_by_component';
    }

   public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $movements = $toolResult['data']['stock_movements'] ?? [];
        $componentId = $toolResult['data']['component_id'] ?? null;
        $count = $toolResult['data']['count'] ?? count($movements);
        $shown = min(self::PREVIEW_LIMIT, count($movements));
        $componentName = $movements[0]['component_name'] ?? "component {$componentId}";
        $componentSku = $movements[0]['component_sku'] ?? null;

        $componentLabel = $componentSku
            ? "{$componentName} ({$componentSku})"
            : $componentName;
        $answer = $count === 0
        ? "I found no stock movements for {$componentLabel}."
        : "I found {$count} stock movement" . ($count === 1 ? '' : 's') . " for {$componentLabel}. Showing {$shown}.";

        return $this->success(
            $answer,
            $toolResult,
            $role,
            [
                'component_id' => $componentId,
                'component_name' => $componentName,
                'component_sku' => $componentSku,
                'total_movements' => $count,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $this->preview($movements, [
                'id',
                'component_name',
                'component_sku',
                'type',
                'quantity',
                'reason',
                'reference_type',
                'reference_id',
                'created_at',
            ]),
            [
                'has_more' => $count > $shown,
                'total' => $count,
                'shown' => $shown,
                'shown_this_response' => $shown,
            ],
            $count > $shown
                ? ['Ask to see more stock movements', 'Open stock page']
                : ['Open stock page']
        );
    }
}