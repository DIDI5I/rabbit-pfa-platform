<?php

namespace App\Repositories;

class SupplierRepository extends Repository
{
    public function existsById(int $id): bool
    {
        return $this
            ->query(
                "SELECT id FROM suppliers WHERE id = ? LIMIT 1",
                [$id]
            )
            ->exists();
    }

    public function findById(int $id): ?array
    {
        return $this
            ->query(
                "SELECT id, name, email, phone, country, rating, is_active
                 FROM suppliers
                 WHERE id = ? LIMIT 1",
                [$id]
            )
            ->fetchOne();
    }
}