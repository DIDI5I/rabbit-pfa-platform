<?php

namespace App\Services\Chatbot\Presenters\StockIntelligence;

use App\Services\Chatbot\Presenters\BaseResponsePresenter;

class StockIntelligenceExplanationPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'stock_intelligence_explanation';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';
        $item = $toolResult['data']['item'] ?? null;
        $engine = $toolResult['data']['engine'] ?? [];

        if (($toolResult['status'] ?? null) !== 'success' || !is_array($item)) {
            return $this->success(
                'I could not find stock intelligence for that product.',
                $toolResult,
                $role,
                [
                    'found' => false,
                    'product_id' => $toolResult['data']['product_id'] ?? null,
                ],
                [],
                [
                    'has_more' => false,
                ],
                ['Show reorder recommendations']
            );
        }

        $name = $item['name'] ?? 'This product';
        $sku = $item['sku'] ?? null;
        $label = $sku ? "{$name} ({$sku})" : $name;

        $recommendation = (bool) ($item['recommendation'] ?? false);
        $priority = $item['priority'] ?? 'NONE';
        $confidence = $item['confidence'] ?? 'UNKNOWN';
        $selectedModel = $item['selected_model'] ?? 'unknown_model';
        $modelReason = $item['model_reason'] ?? 'No model reason was provided.';

        $currentStock = $item['current_stock'] ?? null;
        $reorderPoint = $item['reorder_point'] ?? null;
        $recommendedQty = $item['recommended_reorder_quantity'] ?? null;

        if ($recommendation) {
            $answer = "{$label} is recommended for reorder.";
        } else {
            $answer = "{$label} is not currently recommended for reorder.";
        }

        $answer .= " The selected model is {$selectedModel}: {$modelReason}";

        if ($currentStock !== null && $reorderPoint !== null) {
            $answer .= " Current stock is {$currentStock}, reorder point is {$reorderPoint}.";
        }

        if ($recommendation && $recommendedQty !== null) {
            $answer .= " Recommended reorder quantity is {$recommendedQty}.";
        }

        $answer .= " Priority is {$priority} and confidence is {$confidence}.";

        return $this->success(
            $answer,
            $toolResult,
            $role,
            $this->summary($item, $engine),
            $this->stockPreview($item),
            [
                'has_more' => false,
                'product_sku' => $sku,
                'selected_model' => $selectedModel,
                'priority' => $priority,
                'confidence' => $confidence,
                'calculation_mode' => $engine['calculation_mode'] ?? null,
            ],
            [
                $sku ? "Show product intelligence for {$sku}" : 'Show product intelligence',
                'Show reorder recommendations',
                'Open stock intelligence dashboard',
            ]
        );
    }

    private function summary(array $item, array $engine): array
    {
        return [
            'product_name' => $item['name'] ?? null,
            'product_sku' => $item['sku'] ?? null,
            'category' => $item['category'] ?? null,
            'current_stock' => $item['current_stock'] ?? null,
            'low_stock_threshold' => $item['low_stock_threshold'] ?? null,
            'ved_class' => $item['ved_class'] ?? null,

            'recommendation' => $item['recommendation'] ?? null,
            'priority' => $item['priority'] ?? null,
            'confidence' => $item['confidence'] ?? null,

            'selected_model' => $item['selected_model'] ?? null,
            'model_reason' => $item['model_reason'] ?? null,

            'reason_codes' => $item['reason_codes'] ?? [],
            'data_quality_flags' => $item['data_quality_flags'] ?? [],

            'period_days' => $item['period_days'] ?? ($engine['period_days'] ?? null),
            'forecast_days' => $item['forecast_days'] ?? ($engine['forecast_days'] ?? null),
            'calculation_mode' => $engine['calculation_mode'] ?? null,
        ];
    }

    private function stockPreview(array $item): array
    {
        return [
            [
                'section' => 'stock_state',
                'current_stock' => $item['current_stock'] ?? null,
                'low_stock_threshold' => $item['low_stock_threshold'] ?? null,
                'safety_stock' => $item['safety_stock'] ?? null,
                'reorder_point' => $item['reorder_point'] ?? null,
                'recommended_reorder_quantity' => $item['recommended_reorder_quantity'] ?? null,
            ],
            [
                'section' => 'demand_history',
                'period_days' => $item['period_days'] ?? null,
                'out_movement_count' => $item['out_movement_count'] ?? null,
                'stock_out_quantity' => $item['stock_out_quantity'] ?? null,
                'average_daily_outflow' => $item['average_daily_outflow'] ?? null,
                'outlier_detected' => $item['outlier_detected'] ?? null,
                'excluded_outlier_quantity' => $item['excluded_outlier_quantity'] ?? null,
            ],
            [
                'section' => 'supply_and_value',
                'preferred_supplier_name' => $item['preferred_supplier_name'] ?? null,
                'supplier_lead_time_days' => $item['supplier_lead_time_days'] ?? null,
                'lead_time_estimated' => $item['lead_time_estimated'] ?? null,
                'lead_time_demand' => $item['lead_time_demand'] ?? null,
                'estimated_reorder_value' => $item['estimated_reorder_value'] ?? null,
            ],
            [
                'section' => 'explanation',
                'selected_model' => $item['selected_model'] ?? null,
                'model_reason' => $item['model_reason'] ?? null,
                'reason_codes' => $item['reason_codes'] ?? [],
                'data_quality_flags' => $item['data_quality_flags'] ?? [],
            ],
        ];
    }
}