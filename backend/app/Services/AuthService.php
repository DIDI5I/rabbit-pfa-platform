<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Core\Status;
use App\Core\Session;
use App\Exceptions\ValidationException;
use App\Support\ApiResponse;

class AuthService
{
    public function login(string $email, string $password): array
    {
        $user = (new UserRepository())->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new ValidationException([
                'credentials' => ['Invalid credentials']
            ]);
        }

        if (!(bool) $user['is_active']) {
            throw new ValidationException([
                'account' => ['User account is inactive']
            ]);
        }

        Session::set('user_id', (int) $user['id']);
        Session::set('name', $user['name']);
        Session::set('email', $user['email']);
        Session::set('role', $user['role']);
        Session::set(
            'supplier_company_id',
            isset($user['supplier_company_id'])
                ? (int) $user['supplier_company_id']
                : null
        );

        return ApiResponse::success('Login successful', [
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'company_name' => $user['company_name'],
            ]
        ]);
    }

    public function logout(): array
    {
        session_unset();
        session_destroy();
        return ApiResponse::success('Logout successful');
    }

    public function user(): ?array
    {
        if (!Session::has('user_id')) {
            return null;
        }

        return [
            'id' => Session::get('user_id'),
            'email' => Session::get('email'),
            'role' => Session::get('role'),
            'name' => Session::get('name'),
            'supplier_company_id' => Session::get('supplier_company_id'),
        ];
    }

    public function check(): bool
    {
        return Session::has('user_id');
    }

    public function register(
        string $name,
        string $email,
        string $password,
        string $role,
        ?int $supplierCompanyId
    ): array {
        $userRepo = new UserRepository();

        // 1. Check email uniqueness
        if ($userRepo->findByEmail($email)) {
            throw new ValidationException([
                'email' => ['Email already exists']
            ]);
        }

        // 2. Role-specific logic
        if ($role === 'fournisseur' && !$supplierCompanyId) {
            throw new ValidationException([
                'supplier_company_id' => ['Required for fournisseur role']
            ]);
        }

        if ($role !== 'fournisseur') {
            $supplierCompanyId = null;
        }

        // 3. Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // 4. Create user
        $userRepo->create([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role,
            'supplier_company_id' => $supplierCompanyId
        ]);

        return ApiResponse::success('User registered successfully');
    }
}