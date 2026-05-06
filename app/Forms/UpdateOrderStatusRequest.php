<?php

namespace App\Forms;

use App\Support\Validator;
use App\Exceptions\ValidationException;

class UpdateOrderStatusRequest
{
    private const STATUSES = [
        'pending',
        'paid',
        'processing',
        'shipped',
        'delivered',
        'cancelled',
    ];

    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(): void
    {
        Validator::make($this->data)
            ->required('status')
            ->validate();

        if (!in_array($this->status(), self::STATUSES, true)) {
            throw new ValidationException([
                'status' => ['Invalid order status.'],
            ]);
        }
    }

    public function status(): string
    {
        return strtolower(trim($this->data['status']));
    }
}