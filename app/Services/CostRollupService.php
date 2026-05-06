<?php

namespace App\Services;
use App\Support\ApiResponse;
use App\Repositories\CostRollupRepository;

class CostRollupService
{
    public function calculate(int $productId): array
    {
        $repository = new CostRollupRepository();

        $result = $repository->calculate($productId);
        return ApiResponse::success('Cost rollup calculated successfully', $result);
    }
}