<?php

namespace App\Services\Chatbot\Presenters;

class MultiSourcePresenter
{
    public function present(array $multiResult, array $identity, string $message): array
    {
        $results = $multiResult['data']['results'] ?? [];
        $failures = $multiResult['data']['failures'] ?? [];

        $toolsUsed = [];
        $sources = [];
        $summaries = [];
        $itemsPreview = [];
        $limitations = [];
        $suggestedActions = [];

        foreach ($results as $entry) {
            $intent = $entry['intent'] ?? 'unknown';
            $response = $entry['result'] ?? [];

            $toolsUsed[] = $intent;

            $sources[] = [
                'tool' => $intent,
                'status' => 'used',
            ];

            $data = $response['data'] ?? [];

            $summary =
                $response['data']['summary']
                ?? $response['summary']
                ?? $response['data']
                ?? null;

            if (is_array($summary)) {
                $summaries[$intent] = $summary;
            }

            $preview = $data['items_preview']
                ?? $response['items_preview']
                ?? [];

            if (is_array($preview) && count($preview) > 0) {
                $itemsPreview[] = [
                    'tool' => $intent,
                    'items' => array_slice($preview, 0, 5),
                ];
            }

            foreach (($data['limitations'] ?? []) as $limitation) {
                $limitations[] = $limitation;
            }

            foreach (($data['suggested_actions'] ?? []) as $action) {
                $suggestedActions[] = $action;
            }
        }

        foreach ($failures as $failure) {
            $sources[] = [
                'tool' => $failure['intent'] ?? 'unknown',
                'status' => 'failed',
            ];

            $limitations[] = 'Some requested data could not be loaded: '
                . ($failure['intent'] ?? 'unknown');
        }

        $toolsUsed = array_values(array_unique($toolsUsed));
        $limitations = array_values(array_unique($limitations));
        $suggestedActions = array_values(array_unique($suggestedActions));

        return [
            'message' => 'Multi-source analysis generated successfully.',
            'data' => [
                'answer' => $this->buildBaseAnswer($toolsUsed, $failures, $summaries),
                'intent' => 'multi_source_read',
                'confidence' => 'high',
                'role' => $identity['role'] ?? 'guest',
                'operation_type' => 'read_only',
                'summary' => [
                    'original_message' => $message,
                    'tools_used' => $toolsUsed,
                    'tool_summaries' => $summaries,
                    'failed_tools' => $failures,
                ],
                'items_preview' => $itemsPreview,
                'result_meta' => [
                    'tools_used' => $toolsUsed,
                    'tool_count' => count($toolsUsed),
                    'failed_count' => count($failures),
                ],
                'sources' => $sources,
                'limitations' => $limitations,
                'suggested_actions' => $suggestedActions,
                'ai_refined' => false,
            ],
        ];
    }

   private function buildBaseAnswer(array $toolsUsed, array $failures, array $summaries = []): string
    {
        if (count($toolsUsed) === 0) {
            return 'Rabbit could not load the requested analysis from available tools.';
        }

        $parts = [];

        if (isset($summaries['component_stock_analysis'])) {
            $stock = $summaries['component_stock_analysis'];

            $name = $stock['name'] ?? ('component #' . ($stock['component_id'] ?? 'unknown'));
            $currentStock = $stock['current_stock'] ?? null;
            $threshold = $stock['low_stock_threshold'] ?? null;
            $status = $stock['stock_status'] ?? 'UNKNOWN';
            $risk = $stock['risk_level'] ?? 'unknown';
            $action = $stock['recommended_action'] ?? null;

            $parts[] = "{$name} has current stock {$currentStock}, threshold {$threshold}, status {$status}, and risk level {$risk}."
                . ($action ? " Recommended action: {$action}." : '');
        }

        if (isset($summaries['cost_rollup'])) {
            $cost = $summaries['cost_rollup'];

            $total = $cost['total_material_cost_mad'] ?? null;
            $components = $cost['unique_components'] ?? null;

            if ($total !== null) {
                $parts[] = "The cost rollup totals {$total} MAD"
                    . ($components !== null ? " across {$components} unique component(s)." : '.');
            }
        }

        if (isset($summaries['owner_product_dependencies'])) {
            $deps = $summaries['owner_product_dependencies'];

            $total = $deps['total_dependencies'] ?? null;
            $shown = $deps['shown'] ?? null;

            if ($total !== null) {
                $parts[] = "The product has {$total} registered dependenc" . ((int) $total === 1 ? 'y' : 'ies')
                    . ($shown !== null ? ", with {$shown} shown in this response." : '.');
            }
        }

        if (isset($summaries['reorder_recommendations'])) {
            $reorder = $summaries['reorder_recommendations'];

            $recommended = $reorder['recommended_count'] ?? null;
            $critical = $reorder['critical_count'] ?? null;
            $high = $reorder['high_count'] ?? null;
            $value = $reorder['estimated_reorder_value'] ?? null;

            $parts[] = "Reorder analysis found {$recommended} recommended item(s), including {$critical} critical and {$high} high-priority item(s)"
                . ($value !== null ? ", with estimated reorder value {$value} MAD." : '.');
        }

        if (isset($summaries['stock_intelligence_summary'])) {
            $stockIntel = $summaries['stock_intelligence_summary'];

            $recommended = $stockIntel['recommended_count'] ?? null;
            $critical = $stockIntel['critical_count'] ?? null;
            $high = $stockIntel['high_count'] ?? null;
            $value = $stockIntel['estimated_reorder_value'] ?? null;

            $parts[] = "Stock intelligence found {$recommended} item(s) needing reorder attention, including {$critical} critical and {$high} high-priority item(s)"
                . ($value !== null ? ", worth about {$value} MAD." : '.');
        }

        if (isset($summaries['rfq_summary'])) {
            $rfq = $summaries['rfq_summary'];

            $open = $rfq['open'] ?? 0;
            $quoted = $rfq['quoted'] ?? 0;
            $accepted = $rfq['accepted'] ?? 0;
            $expired = $rfq['expired'] ?? 0;
            $total = $rfq['total'] ?? null;

            $parts[] = "RFQ summary shows {$total} total RFQ(s): {$open} open, {$quoted} quoted, {$accepted} accepted, and {$expired} expired.";
        }

        if (isset($summaries['order_summary'])) {
            $orders = $summaries['order_summary'];

            $total = $orders['total'] ?? null;
            $pending = $orders['pending'] ?? 0;
            $processing = $orders['processing'] ?? 0;
            $shipped = $orders['shipped'] ?? 0;
            $cancelled = $orders['cancelled'] ?? 0;

            $parts[] = "Order summary shows {$total} total order(s): {$pending} pending, {$processing} processing, {$shipped} shipped, and {$cancelled} cancelled.";
        }

        if (count($failures) > 0) {
            $failedTools = array_map(
                fn (array $failure): string => (string) ($failure['intent'] ?? 'unknown'),
                $failures
            );

            $parts[] = 'Some requested parts could not be loaded: ' . implode(', ', $failedTools) . '.';
        }

        if (count($parts) === 0) {
            return 'Rabbit combined data from ' . count($toolsUsed) . ' tool(s): ' . implode(', ', $toolsUsed) . '.';
        }

        return implode(' ', $parts);
    }
}