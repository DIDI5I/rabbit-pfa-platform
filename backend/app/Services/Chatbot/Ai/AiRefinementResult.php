<?php

namespace App\Services\Chatbot\Ai;

class AiRefinementResult
{
    public function __construct(
        public readonly bool $refined,
        public readonly string $answer,
        public readonly array $meta = []
    ) {
    }

    public static function refined(string $answer, array $meta = []): self
    {
        return new self(true, $answer, $meta);
    }

    public static function fallback(string $answer, string $reason, array $meta = []): self
    {
        $meta['fallback_reason'] = $reason;

        return new self(false, $answer, $meta);
    }
}