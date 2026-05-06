<?php

namespace App\Controllers;

use App\Services\OrderService;
use App\Forms\StoreOrderRequest;
use App\Forms\UpdateOrderStatusRequest;

class OrderController
{
    public function index(): array
    {
        $service = new OrderService();

        return $service->all();
    }

    public function show(int $orderId): array
    {
        $service = new OrderService();

        return $service->find($orderId);
    }

    public function store(): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $request = new StoreOrderRequest($data);
        $request->validate();

        $service = new OrderService();

        return $service->create($request);
    }

    public function updateStatus(int $orderId): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $request = new UpdateOrderStatusRequest($data);
        $request->validate();

        $service = new OrderService();

        return $service->updateStatus($orderId, $request);
    }
}