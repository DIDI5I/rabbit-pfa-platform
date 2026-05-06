<?php

namespace App\Support;

use App\Exceptions\ValidationException;

class Validator
{
    private array $errors = [];

    public function __construct(private array $data) {}

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function required(string $field): self
    {
        if (empty($this->data[$field])) {
            $this->errors[$field][] = "$field is required";
        }

        return $this;
    }

    public function email(string $field): self
    {
        if (!filter_var($this->data[$field] ?? null, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "{$field} must be a valid email";
        }

        return $this;
    }

    public function min(string $field, int $length): self
    {
        if (strlen($this->data[$field] ?? '') < $length) {
            $this->errors[$field][] = "{$field} must be at least {$length} characters";
        }

        return $this;
    }

    public function integer(string $field): self
    {
        if (!isset($this->data[$field]) || $this->data[$field] === '') {
            return $this;
        }

        if (filter_var($this->data[$field], FILTER_VALIDATE_INT) === false) {
            $this->errors[$field][] = "{$field} must be an integer";
        }

        return $this;
    }

    public function numeric(string $field): self
    {
        if (!isset($this->data[$field]) || $this->data[$field] === '') {
            return $this;
        }

        if (!is_numeric($this->data[$field])) {
            $this->errors[$field][] = "{$field} must be numeric";
        }

        return $this;
    }

    public function in(string $field, array $allowed): self
    {
        if (!isset($this->data[$field]) || $this->data[$field] === '') {
            return $this;
        }

        if (!in_array($this->data[$field], $allowed, true)) {
            $this->errors[$field][] = "{$field} must be one of: " . implode(', ', $allowed);
        }

        return $this;
    }

    public function validate(): void
    {
        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }
    }

    public function validated(): array
    {
        return $this->data;
    }
}