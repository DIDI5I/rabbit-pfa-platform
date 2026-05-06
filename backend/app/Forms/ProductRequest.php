<?php

namespace App\Forms;

use App\Support\Validator;

class ProductRequest
{
    private array $data;

    private array $allowedCategories = [
        'assembly',
        'sub_assembly',
        'component',
        'raw_material'
    ];

    private array $allowedStockStatuses = [
        'ok',
        'low',
        'out'
    ];

    public function __construct(array $data)
    {

        $this->data = $data;
    }

    public function validate(): void
    {
        Validator::make($this->data)
            ->integer('page')
            ->integer('limit')
            ->in('category', $this->allowedCategories)
            ->in('stock_status', $this->allowedStockStatuses)
            ->numeric('min_price')
            ->numeric('max_price')
            ->numeric('min_supplier_rating')
            ->validate();

        $errors = [];

        if (
            $this->minPrice() !== null &&
            $this->maxPrice() !== null &&
            $this->minPrice() > $this->maxPrice()
        ) {
            $errors['min_price'][] = 'min_price cannot be greater than max_price';
        }

        if (
            $this->minSupplierRating() !== null &&
            ($this->minSupplierRating() < 0 || $this->minSupplierRating() > 5)
        ) {
            $errors['min_supplier_rating'][] = 'must be between 0 and 5';
        }

        if (!empty($errors)) {
            throw new \App\Exceptions\ValidationException($errors);
        }
    }

    // ===== CLEAN GETTERS (same style as LoginRequest) =====

    public function page(): int
    {
        return max(1, (int) ($this->data['page'] ?? 1));
    }

    public function limit(): int
    {
        return max(1, min((int) ($this->data['limit'] ?? 10), 100));
    }

    public function offset(): int
    {
        return ($this->page() - 1) * $this->limit();
    }

    public function search(): ?string
    {
        $value = trim((string) ($this->data['search'] ?? $this->data['q'] ?? ''));

        return $value !== '' ? $value : null;
    }

    public function category(): ?string
    {
        $value = trim((string) ($this->data['category'] ?? ''));
        return $value !== '' ? $value : null;
    }

    public function lowStock(): ?bool
    {
        return $this->parseBool($this->data['low_stock'] ?? null);
    }

    public function supplierId(): ?int
    {
        return isset($this->data['supplier_id']) && is_numeric($this->data['supplier_id'])
            ? (int) $this->data['supplier_id']
            : null;
    }

    public function supplierName(): ?string
    {
        $value = trim((string) ($this->data['supplier_name'] ?? ''));
        return $value !== '' ? $value : null;
    }

    public function supplierCountry(): ?string
    {
        $value = trim((string) ($this->data['supplier_country'] ?? ''));
        return $value !== '' ? $value : null;
    }

    public function minSupplierRating(): ?float
    {
        return isset($this->data['min_supplier_rating']) && is_numeric($this->data['min_supplier_rating'])
            ? (float) $this->data['min_supplier_rating']
            : null;
    }

    public function minPrice(): ?float
    {
        return isset($this->data['min_price']) && is_numeric($this->data['min_price'])
            ? (float) $this->data['min_price']
            : null;
    }

    public function maxPrice(): ?float
    {
        return isset($this->data['max_price']) && is_numeric($this->data['max_price'])
            ? (float) $this->data['max_price']
            : null;
    }

    public function activeOnly(): bool
    {
        return $this->parseBool($this->data['active_only'] ?? null) ?? true;
    }

    private function parseBool(?string $value): ?bool
    {
        if ($value === null) return null;

        $value = strtolower(trim($value));

        if (in_array($value, ['1', 'true', 'yes'], true)) return true;
        if (in_array($value, ['0', 'false', 'no'], true)) return false;

        return null;
    }

    public function stockStatus(): ?string
    {
        $value = trim((string) ($this->data['stock_status'] ?? ''));

        return $value !== '' ? $value : null;
    }
}