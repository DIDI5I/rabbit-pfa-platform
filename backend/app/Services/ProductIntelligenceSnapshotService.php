<?php

namespace App\Services;

use Throwable;

class ProductIntelligenceSnapshotService
{
    private const TECHNICAL_RELATION_TYPES = [
        'technical_structure',
        'replacement_part',
        'compatible_part',
        'spare_part',
        'accessory',
    ];

    private const COMMERCIAL_RELATION_TYPES = [
        'compatible_alternative',
        'similar_product',
        'commercial_alternative',
        'frequently_bought_together',
        'related_product',
    ];

    public function build(int $productId, string $role): array
    {
        $role = $this->normalizeRole($role);

        $snapshot = $this->emptySnapshot();
        $snapshot['data_quality']['calculation_mode'] = 'product_intelligence_snapshot_v1';

        $product = $this->fetchProduct($productId, $snapshot);
        if (!$product) {
            $snapshot['data_quality']['flags'][] = 'product_not_found';
            $snapshot['data_quality']['missing_sections'][] = 'product';

            return $this->sanitizeForRole($snapshot, $role);
        }

        $snapshot['product'] = $this->sanitizeProduct($product, $role);
        $snapshot['product_relationships'] = $this->buildRelationships($productId, $role, $snapshot);
        $snapshot['commercial'] = $this->buildCommercial($productId, $role, $snapshot);
        if ($role === 'owner') {
            $snapshot['reorder'] = $this->buildReorder($productId, $snapshot);
        } else {
            $snapshot['data_quality']['flags'][] = 'internal_sections_hidden_for_role';
        }

        return $this->sanitizeForRole($snapshot, $role);
    }

    private function emptySnapshot(): array
    {
        return [
            'product' => null,

            'product_relationships' => [
                'total_explicit_relations' => 0,
                'compatible_alternatives_count' => 0,
                'accessories_count' => 0,
                'spare_parts_count' => 0,
                'same_category_count' => 0,
                'same_supplier_count' => 0,

                'technical' => [
                    'technical_structure' => 0,
                    'replacement_part' => 0,
                    'compatible_part' => 0,
                    'spare_part' => 0,
                    'accessory' => 0,
                ],

                'commercial' => [
                    'compatible_alternative' => 0,
                    'similar_product' => 0,
                    'commercial_alternative' => 0,
                    'frequently_bought_together' => 0,
                    'related_product' => 0,
                ],

                'groups' => [
                    'explicit_relations' => [],
                    'compatible_alternatives' => [],
                    'accessories' => [],
                    'spare_parts' => [],
                    'same_category' => [],
                    'same_supplier' => [],
                ],

                'grounding' => [],
                'note' => 'Product relationships are generated from backend relations and catalogue data only. No AI guessing is used.',
            ],

            'commercial' => [
                'active_promotions_count' => 0,
                'active_promotions_preview' => [],

                'review_count' => 0,
                'average_rating' => null,
                'rating_distribution' => [
                    '1' => 0,
                    '2' => 0,
                    '3' => 0,
                    '4' => 0,
                    '5' => 0,
                ],

                'reviews_preview' => [],
            ],

            'reorder' => [
                'available' => false,
                'recommendation' => null,
                'priority' => null,
                'confidence' => null,
                'reason_codes' => [],
                'raw_summary' => null,
            ],

            'data_quality' => [
                'calculation_mode' => 'product_intelligence_snapshot_v1',
                'missing_sections' => [],
                'flags' => [],
            ],
        ];
    }

    private function fetchProduct(int $productId, array &$snapshot): ?array
    {
        try {
            $response = (new CatalogProductService())->findById($productId);
            $data = $response['data'] ?? null;

            return is_array($data) ? $data : null;
        } catch (Throwable $e) {
            $snapshot['data_quality']['flags'][] = 'product_fetch_failed';
            $snapshot['data_quality']['missing_sections'][] = 'product';

            return null;
        }
    }

    private function sanitizeProduct(array $product, string $role): array
    {
        $base = [
            'name' => $product['name'] ?? null,
            'sku' => $product['sku'] ?? null,
            'category' => $product['category'] ?? null,
            'description' => $product['description'] ?? null,
            'availability_status' => $product['availability_status'] ?? null,
        ];

        if ($role === 'owner') {
            $base = ['id' => $product['id'] ?? null] + $base;
        }

        return $base;
    }

    private function buildRelationships(int $productId, string $role, array &$snapshot): array
    {
        $result = $this->emptySnapshot()['product_relationships'];

        try {
            $response = (new RecommendationService())->forProduct($productId);
            $data = $response['data'] ?? [];

            $recommendations = $data['recommendations'] ?? [];
            $meta = $data['meta'] ?? [];

            $explicit = $this->safeArray($recommendations['explicit_relations'] ?? []);
            $compatibleAlternatives = $this->safeArray($recommendations['compatible_alternatives'] ?? []);
            $accessories = $this->safeArray($recommendations['accessories'] ?? []);
            $spareParts = $this->safeArray($recommendations['spare_parts'] ?? []);
            $sameCategory = $this->safeArray($recommendations['same_category'] ?? []);
            $sameSupplier = $this->safeArray($recommendations['same_supplier'] ?? []);

            $result['total_explicit_relations'] = count($explicit);
            $result['compatible_alternatives_count'] = count($compatibleAlternatives);
            $result['accessories_count'] = count($accessories);
            $result['spare_parts_count'] = count($spareParts);
            $result['same_category_count'] = count($sameCategory);
            $result['same_supplier_count'] = $role === 'owner' ? count($sameSupplier) : null;

            foreach ($explicit as $relation) {
                $type = (string) ($relation['relation_type'] ?? 'related_product');

                if (in_array($type, self::TECHNICAL_RELATION_TYPES, true)) {
                    $result['technical'][$type] = ($result['technical'][$type] ?? 0) + 1;
                } elseif (in_array($type, self::COMMERCIAL_RELATION_TYPES, true)) {
                    $result['commercial'][$type] = ($result['commercial'][$type] ?? 0) + 1;
                } else {
                    $result['commercial']['related_product']++;
                }
            }

            $result['groups']['explicit_relations'] = $this->sanitizeItems($explicit, $role, 5);
            $result['groups']['compatible_alternatives'] = $this->sanitizeItems($compatibleAlternatives, $role, 5);
            $result['groups']['accessories'] = $this->sanitizeItems($accessories, $role, 5);
            $result['groups']['spare_parts'] = $this->sanitizeItems($spareParts, $role, 5);
            $result['groups']['same_category'] = $this->sanitizeItems($sameCategory, $role, 5);

            if ($role === 'owner') {
                $result['groups']['same_supplier'] = $this->sanitizeItems($sameSupplier, $role, 5);
            } else {
                unset($result['groups']['same_supplier']);
            }

            $result['grounding'] = $meta['grounding'] ?? [];
            $result['note'] = $meta['note']
                ?? 'Recommendations are generated from backend relations and supplier/category data only. No AI guessing is used.';

            return $result;
        } catch (Throwable $e) {
            $snapshot['data_quality']['flags'][] = 'relationships_fetch_failed';
            $snapshot['data_quality']['missing_sections'][] = 'product_relationships';

            return $result;
        }
    }

    private function sanitizeItems(array $items, string $role, int $limit): array
    {
        $items = array_slice($items, 0, $limit);

        return array_map(function (array $item) use ($role) {
            return $this->sanitizeRelatedProduct($item, $role);
        }, $items);
    }

    private function sanitizeRelatedProduct(array $item, string $role): array
    {
        if ($role === 'owner') {
            return [
                'id' => $item['id'] ?? null,
                'name' => $item['name'] ?? null,
                'sku' => $item['sku'] ?? null,
                'category' => $item['category'] ?? null,
                'stock_status' => $item['stock_status'] ?? null,
                'stock_qty' => $item['stock_qty'] ?? null,
                'low_stock_threshold' => $item['low_stock_threshold'] ?? null,
                'relation_type' => $item['relation_type'] ?? null,
            ];
        }

        return [
            'name' => $item['name'] ?? null,
            'sku' => $item['sku'] ?? null,
            'category' => $item['category'] ?? null,
            'availability_status' => $item['availability_status']
                ?? $item['stock_status']
                ?? null,
            'relation_type' => $item['relation_type'] ?? null,
        ];
    }

    private function buildReorder(int $productId, array &$snapshot): array
    {
        $result = [
            'available' => false,
            'recommendation' => null,
            'priority' => null,
            'confidence' => null,
            'reason_codes' => [],
            'raw_summary' => null,
        ];

        try {
            $response = (new StockIntelligenceService())->reorderRecommendations();
            $data = $response['data'] ?? [];

            $items = $this->safeArray($data['items'] ?? []);

            foreach ($items as $item) {
                $itemProductId = (int) (
                    $item['product_id']
                    ?? $item['id']
                    ?? 0
                );

                if ($itemProductId !== $productId) {
                    continue;
                }

                $result['available'] = true;
                $result['recommendation'] = $item['recommendation']
                    ?? $item['recommended_action']
                    ?? $item['should_reorder']
                    ?? null;

                $result['priority'] = $item['priority']
                    ?? $item['risk_priority']
                    ?? $item['severity']
                    ?? null;

                $result['confidence'] = $item['confidence']
                    ?? $item['confidence_level']
                    ?? null;

                $result['reason_codes'] = $item['reason_codes']
                    ?? $item['reasons']
                    ?? [];

                $result['raw_summary'] = [
                    'current_stock' => $item['current_stock'] ?? $item['stock_qty'] ?? null,
                    'forecast_demand' => $item['forecast_demand'] ?? null,
                    'recommended_qty' => $item['recommended_qty'] ?? null,
                    'stockout_risk' => $item['stockout_risk'] ?? null,
                ];

                return $result;
            }

            $snapshot['data_quality']['flags'][] = 'reorder_item_not_found_for_product';

            return $result;
        } catch (Throwable $e) {
            $snapshot['data_quality']['flags'][] = 'reorder_fetch_failed';
            $snapshot['data_quality']['missing_sections'][] = 'reorder';

            return $result;
        }
    }

    private function sanitizeForRole(array $snapshot, string $role): array
    {
        if ($role === 'owner') {
            return $snapshot;
        }

        unset($snapshot['reorder']);

        return $snapshot;
    }

    private function normalizeRole(string $role): string
    {
        if ($role === 'fournisseur') {
            return 'supplier';
        }

        return $role ?: 'guest';
    }

    private function safeArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private function buildCommercial(int $productId, string $role, array &$snapshot): array
    {
        $commercial = $this->emptySnapshot()['commercial'];

        try {
            $promotionResponse = (new CatalogPromotionService())->activeForProduct($productId);
            $promotionData = $promotionResponse['data'] ?? [];

            $promotions = $this->safeArray($promotionData['items'] ?? []);
            $promotionSummary = $promotionData['summary'] ?? [];

            $commercial['active_promotions_count'] = (int) (
                $promotionSummary['total']
                ?? count($promotions)
            );

            $commercial['active_promotions_preview'] = $this->sanitizePromotions($promotions, 3);
        } catch (Throwable $e) {
            $snapshot['data_quality']['flags'][] = 'promotions_fetch_failed';
            $snapshot['data_quality']['missing_sections'][] = 'commercial.promotions';
        }

        try {
            $ratingResponse = (new CatalogReviewService())->ratingSummary($productId);
            $ratingData = $ratingResponse['data'] ?? [];

            $commercial['review_count'] = (int) ($ratingData['review_count'] ?? 0);
            $commercial['average_rating'] = $ratingData['average_rating'] ?? null;
            $commercial['rating_distribution'] = $ratingData['rating_distribution']
                ?? $commercial['rating_distribution'];
        } catch (Throwable $e) {
            $snapshot['data_quality']['flags'][] = 'rating_summary_fetch_failed';
            $snapshot['data_quality']['missing_sections'][] = 'commercial.rating_summary';
        }

        try {
            $reviewResponse = (new CatalogReviewService())->approvedList($productId);
            $reviewData = $reviewResponse['data'] ?? [];

            $reviews = $this->safeArray($reviewData['items'] ?? []);

            $commercial['reviews_preview'] = $this->sanitizeReviews($reviews, 3);
        } catch (Throwable $e) {
            $snapshot['data_quality']['flags'][] = 'reviews_fetch_failed';
            $snapshot['data_quality']['missing_sections'][] = 'commercial.reviews';
        }

        return $commercial;
    }

    private function sanitizePromotions(array $promotions, int $limit): array
    {
        $promotions = array_slice($promotions, 0, $limit);

        return array_map(function (array $promotion) {
            return [
                'title' => $promotion['title'] ?? $promotion['name'] ?? null,
                'description' => $promotion['description'] ?? null,
                'discount_type' => $promotion['discount_type'] ?? null,
                'discount_value' => $promotion['discount_value'] ?? null,
                'starts_at' => $promotion['starts_at'] ?? $promotion['start_date'] ?? null,
                'ends_at' => $promotion['ends_at'] ?? $promotion['end_date'] ?? null,
            ];
        }, $promotions);
    }

    private function sanitizeReviews(array $reviews, int $limit): array
    {
        $reviews = array_slice($reviews, 0, $limit);

        return array_map(function (array $review) {
            return [
                'rating' => $review['rating'] ?? null,
                'title' => $review['title'] ?? null,
                'comment' => $review['comment'] ?? $review['review'] ?? null,
                'created_at' => $review['created_at'] ?? null,
            ];
        }, $reviews);
    }
}