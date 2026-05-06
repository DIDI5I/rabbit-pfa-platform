<?php

namespace App\Forms;

use App\Support\Validator;

class UpdatePromotionRequest
{
    private array $data;

    private array $allowedDiscountTypes = [
        'percentage',
        'fixed_amount',
    ];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(): void
    {
        Validator::make($this->data)
            ->in('discount_type', $this->allowedDiscountTypes)
            ->numeric('discount_value')
            ->validate();
    }

    public function data(): array
    {
        $allowed = [
            'title',
            'description',
            'discount_type',
            'discount_value',
            'starts_at',
            'ends_at',
            'is_active',
        ];

        return array_filter(
            $this->data,
            fn ($key) => in_array($key, $allowed, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}