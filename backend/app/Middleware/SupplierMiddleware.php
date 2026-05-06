<?php

namespace App\Middleware;

use App\Core\Auth;


class SupplierMiddleware
{
    public function handle(): void
    {
        Auth::requireRole('fournisseur');
    }
}