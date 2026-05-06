<?php

namespace App\Forms;

use App\Support\Validator;

class StorePromotionRequest
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
            ->required('title')
            ->required('discount_type')
            ->in('discount_type', $this->allowedDiscountTypes)
            ->required('discount_value')
            ->numeric('discount_value')
            ->required('starts_at')
            ->required('ends_at')
            ->validate();
    }

    public function title(): string
    {
        return trim($this->data['title']);
    }

    public function description(): ?string
    {
        $value = trim((string) ($this->data['description'] ?? ''));
        return $value !== '' ? $value : null;
    }

    public function discountType(): string
    {
        return trim($this->data['discount_type']);
    }

    public function discountValue(): float
    {
        return (float) $this->data['discount_value'];
    }

    public function startsAt(): string
    {
        return trim($this->data['starts_at']);
    }

    public function endsAt(): string
    {
        return trim($this->data['ends_at']);
    }

    public function isActive(): bool
    {
        return array_key_exists('is_active', $this->data)
            ? (bool) $this->data['is_active']
            : true;
    }

    public function createdBy(): ?int
    {
        return isset($this->data['created_by'])
            ? (int) $this->data['created_by']
            : null;
    }
}