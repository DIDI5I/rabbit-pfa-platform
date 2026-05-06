<?php

namespace App\Middleware;

use App\Core\Auth;


class OwnerMiddleware
{
    public function handle(): void
    {
        Auth::requireRole('owner');
    }
}