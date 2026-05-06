<?php

namespace App\Forms;

use App\Support\Validator;
use App\Exceptions\ValidationException;

class StoreDependencyRequest
{
    private const RELATION_TYPES = [
        'technical_structure',
        'replacement_part',
        'compatible_part',
        'compatible_alternative',
        'spare_part',
        'accessory',
        'similar_product',
        'commercial_alternative',
        'frequently_bought_together',
        'related_product',
    ];

    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(): void
    {
        Validator::make($this->data)
            ->required('child_id')
            ->integer('child_id')
            ->required('qty_required')
            ->numeric('qty_required')
            ->validate();

        if ($this->qtyRequired() <= 0) {
            throw new ValidationException([
                'qty_required' => ['Quantity required must be greater than 0.'],
            ]);
        }

        if (!in_array($this->relationType(), self::RELATION_TYPES, true)) {
            throw new ValidationException([
                'relation_type' => ['Invalid relation type.'],
            ]);
        }
    }

    public function childId(): int
    {
        return (int) $this->data['child_id'];
    }

    public function relationType(): string
    {
        return $this->data['relation_type'] ?? 'internal_structure';
    }

    public function qtyRequired(): float
    {
        return (float) $this->data['qty_required'];
    }

    public function uom(): string
    {
        return trim($this->data['uom'] ?? 'pcs');
    }

    public function isPhantom(): bool
    {
        return (bool) ($this->data['is_phantom'] ?? false);
    }

    public function notes(): ?string
    {
        $notes = trim($this->data['notes'] ?? '');

        return $notes !== '' ? $notes : null;
    }
}