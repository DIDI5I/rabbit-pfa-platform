<?php

namespace App\Services\Chatbot\Presenters;

class DashboardStockSummaryPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'dashboard_stock_summary';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $summary = $toolResult['data']['summary'] ?? [];

        $total = $summary['total_products'] ?? $summary['total_items'] ?? 0;
        $low = $summary['low_stock_count'] ?? 0;
        $out = $summary['out_of_stock_count'] ?? 0;

        return $this->success(
            "Stock dashboard summary: {$total} product(s), {$low} low stock, {$out} out of stock.",
            $toolResult,
            $role,
            $summary,
            [],
            [
                'has_more' => false,
                'total' => null,
                'shown' => null,
                'shown_this_response' => null,
            ],
            ['Open dashboard', 'Open inventory page']
        );
    }
}