<?php

namespace App\Services\StockIntelligence;

class StockIntelligenceDiagnostics
{
    public function strip(array $items): array
    {
        return array_map(function (array $item): array {
            unset(
                $item['model_eligibility'],
                $item['trend_analysis'],
                $item['seasonality_analysis'],
                $item['outlier_analysis']
            );

            return $item;
        }, $items);
    }
}