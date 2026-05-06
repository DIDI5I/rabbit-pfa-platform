<?php

namespace App\Controllers;

use App\Forms\CostRollupRequest;
use App\Services\CostRollupService;

class CostRollupController
{
    public function show()
    {
        $request = new CostRollupRequest($_GET);
        $request->validate();

        $service = new CostRollupService();

        return $service->calculate($request->productId());
    }
}