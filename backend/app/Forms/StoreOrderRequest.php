<?php

namespace App\Forms;

use App\Support\Validator;
use App\Exceptions\ValidationException;

class StoreOrderRequest
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(): void
    {
        Validator::make($this->data)
            ->required('items')
            ->required('shipping_address')
            ->validate();

        if (isset($this->data['client_id']) && $this->data['client_id'] !== null && $this->data['client_id'] !== '') {
            Validator::make($this->data)
                ->integer('client_id')
                ->validate();
        }

        if (!is_array($this->data['items']) || count($this->data['items']) === 0) {
            throw new ValidationException([
                'items' => ['Order must contain at least one item.'],
            ]);
        }

        foreach ($this->data['items'] as $index => $item) {
            $this->validateItem($item, $index);
        }
    }

    private function validateItem(array $item, int $index): void
    {
        $field = "items.$index";

        Validator::make($item)
            ->required('component_id')
            ->integer('component_id')
            ->required('quantity')
            ->numeric('quantity')
            ->validate();

        if ((float) $item['quantity'] <= 0) {
            throw new ValidationException([
                "$field.quantity" => ['Quantity must be greater than 0.'],
            ]);
        }

        if (isset($item['unit_price']) && (float) $item['unit_price'] < 0) {
            throw new ValidationException([
                "$field.unit_price" => ['Unit price cannot be negative.'],
            ]);
        }

        if (isset($item['supplier_id']) && $item['supplier_id'] !== null && $item['supplier_id'] !== '') {
            if (!filter_var($item['supplier_id'], FILTER_VALIDATE_INT)) {
                throw new ValidationException([
                    "$field.supplier_id" => ['Supplier id must be an integer.'],
                ]);
            }
        }
    }

    public function clientId(): ?int
    {
        if (!isset($this->data['client_id']) || $this->data['client_id'] === null || $this->data['client_id'] === '') {
            return null;
        }

        return (int) $this->data['client_id'];
    }

    public function shippingAddress(): ?string
    {
        $value = trim($this->data['shipping_address'] ?? '');

        return $value !== '' ? $value : null;
    }

    public function notes(): ?string
    {
        $value = trim($this->data['notes'] ?? '');

        return $value !== '' ? $value : null;
    }

    public function items(): array
    {
        return array_map(function (array $item): array {
            return [
                'component_id' => (int) $item['component_id'],
                'quantity' => (float) $item['quantity'],
                'unit_price' => isset($item['unit_price']) && $item['unit_price'] !== ''
                    ? (float) $item['unit_price']
                    : null,
                'supplier_id' => isset($item['supplier_id']) && $item['supplier_id'] !== ''
                    ? (int) $item['supplier_id']
                    : null,
            ];
        }, $this->data['items']);
    }

    public function data(): array
    {
        return [
            'client_id' => $this->clientId(),
            'shipping_address' => $this->shippingAddress(),
            'notes' => $this->notes(),
            'items' => $this->items(),
        ];
    }
}