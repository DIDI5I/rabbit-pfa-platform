<?php

namespace App\Services;

use App\Repositories\RecommendationRepository;
use App\Support\ApiResponse;

class RecommendationService
{
    private RecommendationRepository $recommendationRepository;

    public function __construct()
    {
        $this->recommendationRepository = new RecommendationRepository();
    }

    public function forProduct(int $productId): array
    {
        $product = $this->recommendationRepository->findProduct($productId);

        if (!$product) {
            return ApiResponse::success(
                'Product recommendations fetched successfully',
                [
                    'product' => null,
                    'recommendations' => [
                        'explicit_relations' => [],
                        'compatible_alternatives' => [],
                        'accessories' => [],
                        'spare_parts' => [],
                        'same_category' => [],
                        'same_supplier' => [],
                    ],
                ]
            );
        }

        $explicitRelations = $this->recommendationRepository
            ->explicitRelations($productId);

        $sameCategory = $this->recommendationRepository
            ->sameCategoryProducts($product['category'], $productId, 6);

        $sameSupplier = $this->recommendationRepository
            ->sameSupplierProducts($productId, 6);

        return ApiResponse::success(
            'Product recommendations fetched successfully',
            [
                'product' => [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'sku' => $product['sku'],
                    'category' => $product['category'],
                    'stock_status' => $product['stock_status'],
                    'matched_source' => $product['matched_source'],
                ],

                'recommendations' => [
                    'explicit_relations' => $explicitRelations,

                    'compatible_alternatives' => $this->filterByRelationType(
                        $explicitRelations,
                        ['compatible_alternative']
                    ),

                    'accessories' => $this->filterByRelationType(
                        $explicitRelations,
                        ['accessory']
                    ),

                    'spare_parts' => $this->filterByRelationType(
                        $explicitRelations,
                        ['spare_part', 'replacement_part']
                    ),

                    'same_category' => $this->removeDuplicateProducts(
                        $sameCategory,
                        $explicitRelations
                    ),

                    'same_supplier' => $this->removeDuplicateProducts(
                        $sameSupplier,
                        $explicitRelations
                    ),
                ],

                'meta' => [
                    'grounding' => [
                        'explicit_relations' => 'dependencies',
                        'same_category' => 'components.category',
                        'same_supplier' => 'part_sources.supplier_id',
                    ],
                    'note' => 'Recommendations are generated from backend relations and supplier/category data only. No AI guessing is used.',
                ],
            ]
        );
    }

    private function filterByRelationType(array $relations, array $types): array
    {
        return array_values(array_filter($relations, function (array $relation) use ($types) {
            return isset($relation['relation_type'])
                && in_array($relation['relation_type'], $types, true);
        }));
    }

    private function removeDuplicateProducts(array $products, array $explicitRelations): array
    {
        $excludedIds = [];

        foreach ($explicitRelations as $relation) {
            $excludedIds[] = (int) $relation['id'];
        }

        $result = [];

        foreach ($products as $product) {
            if (in_array((int) $product['id'], $excludedIds, true)) {
                continue;
            }

            $result[] = $product;
        }

        return $result;
    }
}