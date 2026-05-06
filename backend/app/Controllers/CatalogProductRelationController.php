<?php

namespace App\Controllers;

use App\Services\CatalogProductRelationService;

class CatalogProductRelationController
{
    private CatalogProductRelationService $service;

    public function __construct()
    {
        $this->service = new CatalogProductRelationService();
    }

    public function index(int $id): array
    {
        return $this->service->list($id);
    }
}