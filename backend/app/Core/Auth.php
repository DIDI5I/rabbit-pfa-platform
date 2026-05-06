<?php

namespace App\Core;

use App\Exceptions\ValidationException;

class Auth
{
    public static function require(): void
    {
        if (!Session::has('user_id') || !Session::has('role')) {
            throw new ValidationException([
                'auth' => ['User not authenticated.']
            ]);
        }
    }

    public static function requireRole(string $role): void
    {
        static::require();

        if (Session::get('role') !== $role) {
            throw new ValidationException([
                'role' => ["Only {$role} can perform this action."]
            ]);
        }
    }

    public static function id(): int
    {
        return (int) Session::get('user_id');
    }

    public static function role(): string
    {
        return (string) Session::get('role');
    }

    public static function supplierCompanyId(): ?int
    {
        $id = Session::get('supplier_company_id');

        return $id !== null ? (int) $id : null;
    }

}