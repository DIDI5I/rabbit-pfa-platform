<?php

namespace App\Controllers;

use App\Forms\ProductRequest;
use App\Services\ProductService;
use App\Forms\StoreProductRequest;
use App\Forms\UpdateProductRequest;
use App\Core\Auth;

class ProductController
{
    public function index()
    {
       
        $request = new ProductRequest($_GET);
        $request->validate();

        $service = new ProductService();

        return $service->list($request);
    }

    public function show(int $id)
    {
        $service = new ProductService();

        return $service->show($id);
    }
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $request = new StoreProductRequest($data);
        $request->validate();

        $service = new ProductService();

        return $service->store($request);
    }
    public function update(int $id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $request = new UpdateProductRequest($data);
        $request->validate();

        $service = new ProductService();

        return $service->update($id, $request);
    }

    public function destroy(int $id)
    {
        $service = new ProductService();

        return $service->delete($id);
    }
}