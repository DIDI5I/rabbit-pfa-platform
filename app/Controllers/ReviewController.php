<?php

namespace App\Controllers;

use App\Services\ReviewService;

class ReviewController
{
    private ReviewService $service;

    public function __construct()
    {
        $this->service = new ReviewService();
    }

    public function index(): array
    {
        return $this->service->list();
    }

    public function show(int $id): array
    {
        return $this->service->findById($id);
    }

    public function updateStatus(int $id): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        return $this->service->updateStatus($id, $data);
    }

    public function destroy(int $id): array
    {
        return $this->service->delete($id);
    }
}