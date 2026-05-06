<?php

namespace App\Repositories;

class UserRepository extends Repository
{
    public function findByEmail(string $email): ?array
    {
       
        return $this
            ->query(
                "SELECT id,name, email, password_hash, is_active,role, company_name,supplier_company_id
                 FROM users WHERE email = ? LIMIT 1",
                [$email]
            )
            ->fetchOne();
    }

    public function findById(int $id): ?array
    {
        return $this
            ->query(
                "SELECT id, email FROM users WHERE id = ? LIMIT 1",
                [$id]
            )
            ->fetchOne();
    }

    public function create(array $data): void
    {
        $this->query(
            "INSERT INTO users (name, email, password_hash, role, supplier_company_id)
            VALUES (?, ?, ?, ?, ?)",
            [
                $data['name'],
                $data['email'],
                $data['password_hash'],
                $data['role'],
                $data['supplier_company_id']
            ]
        );
    }

    public function findSupplierUsersByCompanyId(int $supplierCompanyId): array
    {
        return $this
            ->query(
                "SELECT id, name, email, role, supplier_company_id
                FROM users
                WHERE role = ?
                AND supplier_company_id = ?
                AND is_active = 1",
                ['fournisseur', $supplierCompanyId]
            )
            ->fetchMany();
    }
}