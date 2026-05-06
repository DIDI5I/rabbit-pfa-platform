<?php

namespace App\Controllers;

use App\Forms\StorePurchaseLotRequest;
use App\Services\PurchaseLotService;

class PurchaseLotController
{
    public function index(): array
    {
        $service = new PurchaseLotService();

        return $service->all();
    }

    public function show(int $id): array
    {
        $service = new PurchaseLotService();

        return $service->find($id);
    }

    public function store(): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $request = new StorePurchaseLotRequest($data);
        $request->validate();

        $createdBy = $_SESSION['user']['id'] ?? null;

        $service = new PurchaseLotService();

        return $service->create($request, $createdBy);
    }

    public function byProduct(int $productId): array
    {
        $service = new PurchaseLotService();

        return $service->byProduct($productId);
    }

    public function finalize(int $id): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $createdBy = $_SESSION['user']['id'] ?? null;

        $service = new PurchaseLotService();

        return $service->finalize($id, $data, $createdBy);
    }
}