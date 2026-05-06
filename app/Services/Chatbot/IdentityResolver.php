<?php

namespace App\Services\Chatbot;

use App\Core\Session;

class IdentityResolver
{
    public function resolve(): array
    {
        if (!Session::has('user_id')) {
            return [
                'is_authenticated' => false,
                'user_id' => null,
                'role' => 'guest',
                'raw_role' => 'guest',
                'user' => null,
            ];
        }

        $rawRole = Session::get('role') ?? 'guest';
        $role = $this->normalizeRole($rawRole);

        return [
            'is_authenticated' => true,
            'user_id' => Session::get('user_id'),
            'role' => $role,
            'raw_role' => $rawRole,
            'user' => [
                'id' => Session::get('user_id'),
                'name' => Session::get('name'),
                'email' => Session::get('email'),
                'role' => $role,
                'raw_role' => $rawRole,
                'supplier_company_id' => Session::get('supplier_company_id'),
            ],
        ];
    }

    private function normalizeRole(string $role): string
    {
        return match ($role) {
            'fournisseur' => 'supplier',
            default => $role,
        };
    }
}