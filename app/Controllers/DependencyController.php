<?php

namespace App\Controllers;

use App\Services\DependencyService;
use App\Forms\StoreDependencyRequest;
use App\Forms\UpdateDependencyRequest;

class DependencyController
{
    public function index(int $id): array
    {
        $service = new DependencyService();

        return $service->listForProduct($id);
    }

    public function store(int $id): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $request = new StoreDependencyRequest($data);
        $request->validate();

        $service = new DependencyService();

        return $service->addChild($id, $request);
    }

    public function update(int $id, int $dependencyId): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $request = new UpdateDependencyRequest($data);
        $request->validate();

        $service = new DependencyService();

        return $service->updateChild($id, $dependencyId, $request);
    }

    public function destroy(int $id, int $dependencyId): array
    {
        $service = new DependencyService();

        return $service->removeChild($id, $dependencyId);
    }
}