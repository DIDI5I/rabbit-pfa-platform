<?php

namespace App\Forms;

use App\Support\Validator;

class CostRollupRequest
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(): void
    {
        Validator::make($this->data)
            ->required('id')
            ->integer('id')
            ->validate();
    }

    public function productId(): int
    {
        return (int) $this->data['id'];
    }
}