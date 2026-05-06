<?php

namespace App\Forms;

use App\Support\Validator;

class StoreProductRequest
{
    private array $data;

    private array $allowedCategories = [
        'assembly',
        'sub_assembly',
        'component',
        'raw_material'
    ];

    private array $allowedVedClasses = [
        'V',
        'E',
        'D',
    ];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(): void
    {
        $validator = Validator::make($this->data)
            ->required('name')
            ->required('sku')
            ->required('category')
            ->in('category', $this->allowedCategories)
            ->required('unit_of_measure')
            ->numeric('stock_qty')
            ->numeric('low_stock_threshold');

        if (isset($this->data['ved_class']) && $this->data['ved_class'] !== null && $this->data['ved_class'] !== '') {
            $validator->in('ved_class', $this->allowedVedClasses);
        }

        $validator->validate();
    }

    public function name(): string
    {
        return trim($this->data['name']);
    }

    public function sku(): string
    {
        return trim($this->data['sku']);
    }

    public function description(): ?string
    {
        $value = trim((string) ($this->data['description'] ?? ''));
        return $value !== '' ? $value : null;
    }

    public function category(): string
    {
        return trim($this->data['category']);
    }

    public function unitOfMeasure(): string
    {
        return trim($this->data['unit_of_measure']);
    }

    public function stockQty(): float
    {
        return (float) ($this->data['stock_qty'] ?? 0);
    }

    public function lowStockThreshold(): float
    {
        return (float) ($this->data['low_stock_threshold'] ?? 0);
    }

    public function vedClass(): ?string
    {
        $value = trim((string) ($this->data['ved_class'] ?? ''));

        return $value !== '' ? $value : null;
    }
}