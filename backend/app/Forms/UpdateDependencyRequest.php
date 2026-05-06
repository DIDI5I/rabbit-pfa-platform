<?php

namespace App\Forms;

use App\Support\Validator;
use App\Exceptions\ValidationException;

class UpdateDependencyRequest
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
            ->numeric('qty_required')
            ->validate();

        if (isset($this->data['qty_required']) && (float) $this->data['qty_required'] <= 0) {
            throw new ValidationException([
                'qty_required' => ['Quantity required must be greater than 0.'],
            ]);
        }

        if (
            isset($this->data['relation_type']) &&
            !in_array($this->data['relation_type'], self::RELATION_TYPES, true)
        ) {
            throw new ValidationException([
                'relation_type' => ['Invalid relation type.'],
            ]);
        }
    }

    public function data(): array
    {
        $allowed = [
            'relation_type',
            'qty_required',
            'uom',
            'is_phantom',
            'notes',
        ];

        $data = array_filter(
            $this->data,
            fn ($key) => in_array($key, $allowed, true),
            ARRAY_FILTER_USE_KEY
        );

        if (isset($data['relation_type'])) {
            $data['relation_type'] = trim($data['relation_type']);
        }

        if (isset($data['qty_required'])) {
            $data['qty_required'] = (float) $data['qty_required'];
        }

        if (isset($data['uom'])) {
            $data['uom'] = trim($data['uom']) ?: 'pcs';
        }

        if (isset($data['is_phantom'])) {
            $data['is_phantom'] = (bool) $data['is_phantom'];
        }

        if (array_key_exists('notes', $data)) {
            $notes = trim((string) $data['notes']);
            $data['notes'] = $notes !== '' ? $notes : null;
        }

        return $data;
    }
}