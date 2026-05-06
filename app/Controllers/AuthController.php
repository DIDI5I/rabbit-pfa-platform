<?php

namespace App\Controllers;

use App\Forms\LoginRequest;
use App\Forms\RegisterRequest;
use App\Services\AuthService;
use App\Core\Session;
use App\Support\ApiResponse;

class AuthController
{
    public function login(): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $request = new LoginRequest($data);
        $request->validate();

        $authService = new AuthService();

        return $authService->login(
            $request->email(),
            $request->password()
        );
    }

    public function register(): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $request = new RegisterRequest($data);
        $request->validate();

        $authService = new AuthService();

        return $authService->register(
            $request->name(),
            $request->email(),
            $request->password(),
            $request->role(),
            $request->supplierCompanyId()
        );
    }

    public function logout(): array
    {
        Session::destroy();
        return ApiResponse::success('Logout successful');
    }
}