<?php

namespace App\Controllers;

use App\Services\CatalogProductService;

class CatalogProductController
{
    private CatalogProductService $service;

    public function __construct()
    {
        $this->service = new CatalogProductService();
    }

    public function index(): array
    {
        return $this->service->list($_GET);
    }

    public function show(int $id): array
    {
        return $this->service->findById($id);
    }
}