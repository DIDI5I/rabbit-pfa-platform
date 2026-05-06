<?php

namespace App\Services\Chatbot\Ai;

class AiClientResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $text = null,
        public readonly ?string $error = null,
        public readonly array $meta = []
    ) {
    }

    public static function success(string $text, array $meta = []): self
    {
        return new self(true, $text, null, $meta);
    }

    public static function failure(string $error, array $meta = []): self
    {
        return new self(false, null, $error, $meta);
    }
}