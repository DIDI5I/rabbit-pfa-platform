<?php

namespace App\Forms;

use App\Support\Validator;
use App\Exceptions\ValidationException;

class StoreStockMovementRequest
{
    private const TYPES = [
        'in',
        'out',
    ];

    private const REASONS = [
        'INITIAL_STOCK',
        'RFQ_ACCEPTED',
        'PURCHASE_RECEIVED',
        'SALE',
        'MANUAL_ADJUSTMENT',
        'RETURN',
        'DAMAGED',
        'CANCELLED_ORDER_RESTORE',
    ];

    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(): void
    {
        Validator::make($this->data)
            ->required('component_id')
            ->integer('component_id')
            ->required('type')
            ->required('quantity')
            ->numeric('quantity')
            ->required('reason')
            ->validate();

        if (!in_array($this->type(), self::TYPES, true)) {
            throw new ValidationException([
                'type' => ['Invalid stock movement type.'],
            ]);
        }

        if ($this->quantity() <= 0) {
            throw new ValidationException([
                'quantity' => ['Quantity must be greater than 0.'],
            ]);
        }

        if (!in_array($this->reason(), self::REASONS, true)) {
            throw new ValidationException([
                'reason' => ['Invalid stock movement reason.'],
            ]);
        }

        $this->validateReasonMatchesType();
    }

    private function validateReasonMatchesType(): void
    {
        $inReasons = [
            'INITIAL_STOCK',
            'RFQ_ACCEPTED',
            'PURCHASE_RECEIVED',
            'RETURN',
            'MANUAL_ADJUSTMENT',
            'CANCELLED_ORDER_RESTORE',
        ];

        $outReasons = [
            'SALE',
            'DAMAGED',
            'MANUAL_ADJUSTMENT',
        ];

        if ($this->type() === 'in' && !in_array($this->reason(), $inReasons, true)) {
            throw new ValidationException([
                'reason' => ['This reason is not valid for an IN stock movement.'],
            ]);
        }

        if ($this->type() === 'out' && !in_array($this->reason(), $outReasons, true)) {
            throw new ValidationException([
                'reason' => ['This reason is not valid for an OUT stock movement.'],
            ]);
        }
    }

    public function componentId(): int
    {
        return (int) $this->data['component_id'];
    }

    public function type(): string
    {
        return strtolower(trim($this->data['type']));
    }

    public function quantity(): float
    {
        return (float) $this->data['quantity'];
    }

    public function reason(): string
    {
        return strtoupper(trim($this->data['reason']));
    }

    public function referenceType(): ?string
    {
        $value = trim($this->data['reference_type'] ?? '');

        return $value !== '' ? $value : null;
    }

    public function referenceId(): ?int
    {
        if (!isset($this->data['reference_id']) || $this->data['reference_id'] === null || $this->data['reference_id'] === '') {
            return null;
        }

        return (int) $this->data['reference_id'];
    }

    public function notes(): ?string
    {
        $value = trim($this->data['notes'] ?? '');

        return $value !== '' ? $value : null;
    }

    public function data(): array
    {
        return [
            'component_id' => $this->componentId(),
            'type' => $this->type(),
            'quantity' => $this->quantity(),
            'reason' => $this->reason(),
            'reference_type' => $this->referenceType(),
            'reference_id' => $this->referenceId(),
            'notes' => $this->notes(),
        ];
    }
}