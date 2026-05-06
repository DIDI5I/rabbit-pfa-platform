<?php

namespace App\Controllers;

use App\Forms\RFQs\AcceptRfqRequest;
use App\Forms\RFQs\CreateRfqRequest;
use App\Forms\RFQs\QuoteRfqRequest;
use App\Services\RfqService;

class RfqController
{
    protected RfqService $service;

    public function __construct()
    {
        $this->service = new RfqService();
    }

    public function create(): array
    {
        $request = new CreateRfqRequest();

        return $this->service->create($request);
    }

    public function index(): array
    {
        return $this->service->list();
    }

    public function show(int $id): array
    {
        return $this->service->detail($id);
    }

    public function quote(int $id): array
    {
        $request = new QuoteRfqRequest();

        return $this->service->quote($id, $request);
    }

    public function accept(): array
    {
        $request = new AcceptRfqRequest();

        return $this->service->accept($request);
    }

    public function reject(): array
    {
        $request = new AcceptRfqRequest();

        return $this->service->reject($request);
    }

    public function expire(): array
    {
        $request = new AcceptRfqRequest();

        return $this->service->expire($request);
    }

    public function open(): array
    {
        $request = new AcceptRfqRequest();

        return $this->service->open($request);
    }

    public function actions(int $id): array
    {
        return $this->service->getAllowedActions($id);
    }


}