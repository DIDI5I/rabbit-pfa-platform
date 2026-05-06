<?php

namespace App\Forms;

use App\Support\Validator;
use App\Exceptions\ValidationException;

class StorePurchaseLotRequest
{
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
            ->required('supplier_id')
            ->integer('supplier_id')
            ->required('quantity_received')
            ->numeric('quantity_received')
            ->required('supplier_unit_price')
            ->numeric('supplier_unit_price')
            ->required('purchase_date')
            ->validate();

        if ($this->quantityReceived() <= 0) {
            throw new ValidationException([
                'quantity_received' => ['quantity_received must be greater than 0.'],
            ]);
        }

        if ($this->supplierUnitPrice() < 0) {
            throw new ValidationException([
                'supplier_unit_price' => ['supplier_unit_price cannot be negative.'],
            ]);
        }

        foreach ($this->costFields() as $field) {
            if (!isset($this->data[$field]) || $this->data[$field] === '' || $this->data[$field] === null) {
                continue;
            }

            if (!is_numeric($this->data[$field])) {
                throw new ValidationException([
                    $field => ["{$field} must be numeric."],
                ]);
            }

            if ((float) $this->data[$field] < 0) {
                throw new ValidationException([
                    $field => ["{$field} cannot be negative."],
                ]);
            }
        }

        if (!$this->isValidDate($this->purchaseDate())) {
            throw new ValidationException([
                'purchase_date' => ['purchase_date must be a valid date in YYYY-MM-DD format.'],
            ]);
        }

        if (
            isset($this->data['reference_id']) &&
            $this->data['reference_id'] !== null &&
            $this->data['reference_id'] !== '' &&
            !filter_var($this->data['reference_id'], FILTER_VALIDATE_INT)
        ) {
            throw new ValidationException([
                'reference_id' => ['reference_id must be an integer.'],
            ]);
        }
    }

    public function componentId(): int
    {
        return (int) $this->data['component_id'];
    }

    public function supplierId(): int
    {
        return (int) $this->data['supplier_id'];
    }

    public function quantityReceived(): float
    {
        return (float) $this->data['quantity_received'];
    }

    public function supplierUnitPrice(): float
    {
        return (float) $this->data['supplier_unit_price'];
    }

    public function supplierTotalPrice(): float
    {
        return round($this->quantityReceived() * $this->supplierUnitPrice(), 2);
    }

    public function transportCost(): float
    {
        return $this->optionalFloat('transport_cost');
    }

    public function customsCost(): float
    {
        return $this->optionalFloat('customs_cost');
    }

    public function handlingCost(): float
    {
        return $this->optionalFloat('handling_cost');
    }

    public function packagingCost(): float
    {
        return $this->optionalFloat('packaging_cost');
    }

    public function orderPreparationCost(): float
    {
        return $this->optionalFloat('order_preparation_cost');
    }

    public function otherCost(): float
    {
        return $this->optionalFloat('other_cost');
    }

    public function totalPurchaseCost(): float
    {
        return round(
            $this->supplierTotalPrice()
            + $this->transportCost()
            + $this->customsCost()
            + $this->handlingCost()
            + $this->packagingCost()
            + $this->orderPreparationCost()
            + $this->otherCost(),
            2
        );
    }

    public function unitPurchaseCost(): float
    {
        return round($this->totalPurchaseCost() / $this->quantityReceived(), 4);
    }

    public function purchaseDate(): string
    {
        return trim((string) $this->data['purchase_date']);
    }

    public function referenceType(): ?string
    {
        $value = trim((string) ($this->data['reference_type'] ?? ''));

        return $value !== '' ? $value : null;
    }

    public function referenceId(): ?int
    {
        if (
            !isset($this->data['reference_id']) ||
            $this->data['reference_id'] === null ||
            $this->data['reference_id'] === ''
        ) {
            return null;
        }

        return (int) $this->data['reference_id'];
    }

    public function notes(): ?string
    {
        $value = trim((string) ($this->data['notes'] ?? ''));

        return $value !== '' ? $value : null;
    }

    public function data(): array
    {
        return [
            'component_id' => $this->componentId(),
            'supplier_id' => $this->supplierId(),
            'quantity_received' => $this->quantityReceived(),
            'supplier_unit_price' => $this->supplierUnitPrice(),
            'supplier_total_price' => $this->supplierTotalPrice(),
            'transport_cost' => $this->transportCost(),
            'customs_cost' => $this->customsCost(),
            'handling_cost' => $this->handlingCost(),
            'packaging_cost' => $this->packagingCost(),
            'order_preparation_cost' => $this->orderPreparationCost(),
            'other_cost' => $this->otherCost(),
            'total_purchase_cost' => $this->totalPurchaseCost(),
            'unit_purchase_cost' => $this->unitPurchaseCost(),
            'status' => $this->status(),
            'purchase_date' => $this->purchaseDate(),
            'reference_type' => $this->referenceType(),
            'reference_id' => $this->referenceId(),
            'notes' => $this->notes(),
        ];
    }

    private function optionalFloat(string $field): float
    {
        if (
            !isset($this->data[$field]) ||
            $this->data[$field] === '' ||
            $this->data[$field] === null
        ) {
            return 0.0;
        }

        return (float) $this->data[$field];
    }

    private function costFields(): array
    {
        return [
            'transport_cost',
            'customs_cost',
            'handling_cost',
            'packaging_cost',
            'order_preparation_cost',
            'other_cost',
        ];
    }

    private function isValidDate(string $date): bool
    {
        $parts = explode('-', $date);

        if (count($parts) !== 3) {
            return false;
        }

        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }

    public function status(): string
    {
        $value = strtolower(trim((string) ($this->data['status'] ?? 'draft')));

        if (!in_array($value, ['draft', 'finalized', 'cancelled'], true)) {
            return 'draft';
        }

        return $value;
    }
}