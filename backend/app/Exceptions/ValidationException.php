<?php

namespace App\Exceptions;

use Exception;

class ValidationException extends Exception
{
    private array $fields;

    public function __construct(array $fields)
    {
        parent::__construct('Validation failed');
        $this->fields = $fields;
    }

    public function fields(): array
    {
        return $this->fields;
    }
}