<?php

namespace App\Forms;

use App\Support\Validator;

class UpdateProductRequest
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
            ->in('category', $this->allowedCategories)
            ->numeric('stock_qty')
            ->numeric('low_stock_threshold');

        if (isset($this->data['ved_class']) && $this->data['ved_class'] !== null && $this->data['ved_class'] !== '') {
            $validator->in('ved_class', $this->allowedVedClasses);
        }

        $validator->validate();
    }
    public function data(): array
    {
        $allowed = [
            'name',
            'sku',
            'description',
            'category',
            'unit_of_measure',
            'stock_qty',
            'low_stock_threshold',
            'ved_class',
            'is_active'
        ];

        return array_filter(
            $this->data,
            fn ($key) => in_array($key, $allowed, true),
            ARRAY_FILTER_USE_KEY
        );
    }
    public function has(string $field): bool
    {
        return array_key_exists($field, $this->data);
    }
}