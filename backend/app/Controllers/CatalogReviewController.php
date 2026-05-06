<?php

namespace App\Controllers;

use App\Services\CatalogReviewService;

class CatalogReviewController
{
    private CatalogReviewService $service;

    public function __construct()
    {
        $this->service = new CatalogReviewService();
    }

    public function store(int $id): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        return $this->service->create($id, $data);
    }

    public function index(int $id): array
    {
        return $this->service->approvedList($id);
    }

    public function ratingSummary(int $id): array
    {
        return $this->service->ratingSummary($id);
    }
}