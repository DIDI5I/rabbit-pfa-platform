<?php

namespace App\Forms;

use App\Support\Validator;
use App\Exceptions\ValidationException;

class RegisterRequest
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(): void
    {
        Validator::make($this->data)
            ->required('name')
            ->required('email')
            ->email('email')
            ->required('password')
            ->min('password', 6)
            ->required('role')
            ->in('role', ['owner', 'client', 'fournisseur'])
            ->validate();

        $role = $this->role();

        if ($role === 'fournisseur' && empty($this->data['supplier_company_id'])) {
            throw new ValidationException([
                'supplier_company_id' => ['supplier_company_id is required for fournisseur role']
            ]);
        }

        if ($role !== 'fournisseur' && !empty($this->data['supplier_company_id'])) {
            throw new ValidationException([
                'supplier_company_id' => ['supplier_company_id is only allowed for fournisseur role']
            ]);
        }
    }

    public function name(): string
    {
        return trim($this->data['name']);
    }

    public function email(): string
    {
        return trim($this->data['email']);
    }

    public function password(): string
    {
        return $this->data['password'];
    }

    public function role(): string
    {
        return $this->data['role'];
    }

    public function supplierCompanyId(): ?int
    {
        return isset($this->data['supplier_company_id'])
            ? (int) $this->data['supplier_company_id']
            : null;
    }
}