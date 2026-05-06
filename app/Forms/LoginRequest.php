<?php

namespace App\Forms;

use App\Support\Validator;

class LoginRequest
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(): void
    {
        Validator::make($this->data)
            ->required('email')
            ->email('email')
            ->required('password')
            ->min('password', 6)
            ->validate();
    }

    public function email(): string
    {
        return trim($this->data['email']);
    }

    public function password(): string
    {
        return $this->data['password'];
    }
}