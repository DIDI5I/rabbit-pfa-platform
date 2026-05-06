<?php

namespace App\Forms;

use App\Support\Validator;

class UpdateReviewStatusRequest
{
    private array $data;

    private array $allowedStatuses = [
        'pending',
        'approved',
        'rejected',
    ];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(): void
    {
        Validator::make($this->data)
            ->required('status')
            ->in('status', $this->allowedStatuses)
            ->validate();
    }

    public function status(): string
    {
        return trim((string) $this->data['status']);
    }
}