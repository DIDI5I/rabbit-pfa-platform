<?php

namespace App\Controllers;

use App\Services\PromotionService;

class PromotionController
{
    private PromotionService $service;

    public function __construct()
    {
        $this->service = new PromotionService();
    }

    public function index(): array
    {
        return $this->service->list();
    }

    public function show(int $id): array
    {
        return $this->service->findById($id);
    }

    public function store(): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        return $this->service->create($data);
    }

    public function update(int $id): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        return $this->service->update($id, $data);
    }

    public function destroy(int $id): array
    {
        return $this->service->delete($id);
    }
}