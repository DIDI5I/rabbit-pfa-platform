<?php

namespace App\Services\Chatbot\Presenters;

class CostRollupPresenter extends BaseResponsePresenter
{
    public function supports(string $tool): bool
    {
        return $tool === 'cost_rollup';
    }

    public function present(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';

        $data = $toolResult['data'];

        $productId = $data['product_id'] ?? null;
        $cost = $data['total_material_cost_mad'] ?? $data['total_material_cost'] ?? null;
        $components = $data['unique_components'] ?? null;

        return $this->success(
            "Cost rollup for product {$productId} is {$cost} MAD across {$components} unique component(s).",
            $toolResult,
            $role,
            [
                'product_id' => $productId,
                'total_material_cost_mad' => $cost,
                'unique_components' => $components,
            ],
            [],
            [
                'has_more' => false,
                'total' => 1,
                'shown' => 1,
                'shown_this_response' => 1,
            ],
            ['Open product details']
        );
    }
}