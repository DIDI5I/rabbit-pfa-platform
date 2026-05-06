<?php

namespace App\Services\Chatbot\Presenters\ProductIntelligence;

use App\Services\Chatbot\Presenters\BaseResponsePresenter;

class ProductIntelligenceSnapshotPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'product_intelligence_snapshot';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $snapshot = $toolResult['data']['snapshot'] ?? [];
        $product = $snapshot['product'] ?? null;

        if (($toolResult['status'] ?? null) !== 'success' || !$product) {
            return $this->success(
                'I could not build a product intelligence snapshot for that product.',
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
                ['Search catalogue']
            );
        }

        $relationships = $snapshot['product_relationships'] ?? [];
        $reorder = $snapshot['reorder'] ?? null;
        $quality = $snapshot['data_quality'] ?? [];

        $productName = $product['name'] ?? 'This product';
        $sku = $product['sku'] ?? null;

        $label = $sku
            ? "{$productName} ({$sku})"
            : $productName;

        $totalExplicit = (int) ($relationships['total_explicit_relations'] ?? 0);
        $sameCategory = (int) ($relationships['same_category_count'] ?? 0);
        $sameSupplier = $relationships['same_supplier_count'] ?? null;

        $answer = "{$label} has a product intelligence snapshot.";

        if ($totalExplicit > 0) {
            $answer .= " It has {$totalExplicit} explicit product relationship" . ($totalExplicit === 1 ? '' : 's') . ".";
        } else {
            $answer .= " No explicit product relationships were found.";
        }

        $commercial = $snapshot['commercial'] ?? [];
        $promotionCount = (int) ($commercial['active_promotions_count'] ?? 0);
        $reviewCount = (int) ($commercial['review_count'] ?? 0);
        $averageRating = $commercial['average_rating'] ?? null;

        if ($promotionCount > 0) {
            $answer .= " It has {$promotionCount} active promotion" . ($promotionCount === 1 ? '' : 's') . ".";
        }

        if ($reviewCount > 0 && $averageRating !== null) {
            $answer .= " It has an average rating of {$averageRating} from {$reviewCount} review" . ($reviewCount === 1 ? '' : 's') . ".";
        }

        if ($role === 'owner' && is_array($reorder) && ($reorder['available'] ?? false)) {
            $priority = $reorder['priority'] ?? 'unknown';
            $confidence = $reorder['confidence'] ?? 'unknown';

            $answer .= " Owner stock intelligence is available with priority {$priority} and confidence {$confidence}.";
        }

        if ($role !== 'owner') {
            $answer .= " Internal stock, cost, procurement, and reorder sections are hidden for your role.";
        }

        return $this->success(
            $answer,
            $toolResult,
            $role,
            $this->summary($snapshot, $role),
            $this->snapshotPreview($snapshot, $role),
            [
                'has_more' => false,
                'product_sku' => $sku,
                'relationship_count' => $totalExplicit,
                'same_category_count' => $sameCategory,
                'same_supplier_count' => $role === 'owner' ? $sameSupplier : null,
                'calculation_mode' => $quality['calculation_mode'] ?? 'product_intelligence_snapshot_v1',
            ],
            $this->actions($snapshot, $role)
        );
    }

    private function summary(array $snapshot, string $role): array
    {
        $product = $snapshot['product'] ?? [];
        $relationships = $snapshot['product_relationships'] ?? [];
        $reorder = $snapshot['reorder'] ?? null;
        $quality = $snapshot['data_quality'] ?? [];
        $commercial = $snapshot['commercial'] ?? [];

        $summary = [
            'product_name' => $product['name'] ?? null,
            'product_sku' => $product['sku'] ?? null,
            'category' => $product['category'] ?? null,
            'availability_status' => $product['availability_status'] ?? null,
            'explicit_relationships' => $relationships['total_explicit_relations'] ?? 0,
            'compatible_alternatives' => $relationships['compatible_alternatives_count'] ?? 0,
            'accessories' => $relationships['accessories_count'] ?? 0,
            'spare_parts' => $relationships['spare_parts_count'] ?? 0,
            'same_category' => $relationships['same_category_count'] ?? 0,
            'calculation_mode' => $quality['calculation_mode'] ?? 'product_intelligence_snapshot_v1',
            'data_quality_flags' => $quality['flags'] ?? [],
            'missing_sections' => $quality['missing_sections'] ?? [],
            'active_promotions_count' => $commercial['active_promotions_count'] ?? 0,
            'review_count' => $commercial['review_count'] ?? 0,
            'average_rating' => $commercial['average_rating'] ?? null,
        ];

        if ($role === 'owner' && is_array($reorder)) {
            $summary['reorder_available'] = $reorder['available'] ?? false;
            $summary['reorder_priority'] = $reorder['priority'] ?? null;
            $summary['reorder_confidence'] = $reorder['confidence'] ?? null;
            $summary['reorder_reason_codes'] = $reorder['reason_codes'] ?? [];
        }

        return $summary;
    }

    private function snapshotPreview(array $snapshot, string $role): array
    {
        $product = $snapshot['product'] ?? [];
        $relationships = $snapshot['product_relationships'] ?? [];
        $groups = $relationships['groups'] ?? [];

        $preview = [];

        $preview[] = [
            'section' => 'product',
            'name' => $product['name'] ?? null,
            'sku' => $product['sku'] ?? null,
            'category' => $product['category'] ?? null,
            'availability_status' => $product['availability_status'] ?? null,
        ];

        foreach ([
            'compatible_alternatives' => 'Compatible alternatives',
            'spare_parts' => 'Spare parts',
            'accessories' => 'Accessories',
            'same_category' => 'Same category',
        ] as $key => $label) {
            $items = $groups[$key] ?? [];

            if (!is_array($items) || count($items) === 0) {
                continue;
            }

            $preview[] = [
                'section' => $label,
                'count' => count($items),
                'items' => $items,
            ];
        }

        $commercial = $snapshot['commercial'] ?? [];

        $preview[] = [
            'section' => 'commercial',
            'active_promotions_count' => $commercial['active_promotions_count'] ?? 0,
            'review_count' => $commercial['review_count'] ?? 0,
            'average_rating' => $commercial['average_rating'] ?? null,
            'rating_distribution' => $commercial['rating_distribution'] ?? [],
        ];

        $promotions = $commercial['active_promotions_preview'] ?? [];
        if (is_array($promotions) && count($promotions) > 0) {
            $preview[] = [
                'section' => 'Active promotions',
                'count' => count($promotions),
                'items' => $promotions,
            ];
        }

        $reviews = $commercial['reviews_preview'] ?? [];
        if (is_array($reviews) && count($reviews) > 0) {
            $preview[] = [
                'section' => 'Recent reviews',
                'count' => count($reviews),
                'items' => $reviews,
            ];
        }

        if ($role === 'owner') {
            $sameSupplier = $groups['same_supplier'] ?? [];

            if (is_array($sameSupplier) && count($sameSupplier) > 0) {
                $preview[] = [
                    'section' => 'Same supplier',
                    'count' => count($sameSupplier),
                    'items' => $sameSupplier,
                ];
            }

            $reorder = $snapshot['reorder'] ?? null;

            if (is_array($reorder)) {
                $preview[] = [
                    'section' => 'Owner stock intelligence',
                    'available' => $reorder['available'] ?? false,
                    'recommendation' => $reorder['recommendation'] ?? null,
                    'priority' => $reorder['priority'] ?? null,
                    'confidence' => $reorder['confidence'] ?? null,
                    'reason_codes' => $reorder['reason_codes'] ?? [],
                ];
            }
        }

        return $preview;
    }

    private function actions(array $snapshot, string $role): array
    {
        $product = $snapshot['product'] ?? [];
        $sku = $product['sku'] ?? null;

        $actions = [];

        if ($sku) {
            $actions[] = "Show related products for {$sku}";
        }

        if ($role === 'owner' && $sku) {
            $actions[] = "What should I reorder for {$sku}?";
            $actions[] = "Show purchase lots for {$sku}";
        }

        $actions[] = 'Open catalogue';

        return $actions;
    }
}