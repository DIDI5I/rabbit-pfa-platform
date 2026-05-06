<?php

namespace App\Controllers;

use App\Services\RecommendationService;

class RecommendationController
{
    private RecommendationService $recommendationService;

    public function __construct()
    {
        $this->recommendationService = new RecommendationService();
    }

    public function show(int $id): array
    {
        return $this->recommendationService->forProduct($id);
    }
}