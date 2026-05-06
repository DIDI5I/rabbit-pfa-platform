<?php

namespace App\Middleware;

use App\Core\Session;
use App\Exceptions\ValidationException;

class GuestMiddleware
{
    public function handle(): void
    {
        if (Session::has('user_id')) {
            throw new ValidationException([
                'auth' => ['Already authenticated.']
            ]);
        }
    }
}