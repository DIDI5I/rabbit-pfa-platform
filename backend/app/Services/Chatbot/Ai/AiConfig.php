<?php

namespace App\Services\Chatbot\Ai;

class AiConfig
{
    private ?array $config = null;

    public function enabled(): bool
    {
        return $this->bool('enabled', false);
    }

    public function provider(): ?string
    {
        return $this->string('provider');
    }

    public function apiUrl(): ?string
    {
        return $this->string('api_url');
    }

    public function apiKey(): ?string
    {
        return $this->string('api_key');
    }

    public function model(): ?string
    {
        return $this->string('model');
    }

    public function promptVersion(): string
    {
        return $this->string('prompt_version') ?: 'v1.0';
    }

    public function timeoutSeconds(): int
    {
        return max(1, min($this->int('timeout_seconds', 8), 30));
    }

    public function maxInputChars(): int
    {
        return max(1000, min($this->int('max_input_chars', 12000), 50000));
    }

    public function maxOutputChars(): int
    {
        return max(200, min($this->int('max_output_chars', 800), 5000));
    }

    public function logEnabled(): bool
    {
        return $this->bool('log_enabled', true);
    }

    public function strictOutputValidation(): bool
    {
        return $this->bool('strict_output_validation', true);
    }

    public function fallbackOnValidationFailure(): bool
    {
        return $this->bool('fallback_on_validation_failure', true);
    }

    public function allowedIntents(): array
    {
        $value = $this->ai()['allowed_intents'] ?? [];

        if (is_array($value)) {
            return array_values(array_filter(array_map(
                fn ($item) => trim((string) $item),
                $value
            )));
        }

        if (is_string($value)) {
            return array_values(array_filter(array_map(
                fn ($item) => trim($item),
                explode(',', $value)
            )));
        }

        return [];
    }

    public function isIntentAllowed(?string $intent): bool
    {
        if (!$intent) {
            return false;
        }

        return in_array($intent, $this->allowedIntents(), true);
    }

    private function string(string $key): ?string
    {
        $value = $this->ai()[$key] ?? null;

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function int(string $key, int $default): int
    {
        $value = $this->ai()[$key] ?? $default;

        return (int) $value;
    }

    private function bool(string $key, bool $default): bool
    {
        $value = $this->ai()[$key] ?? $default;

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return $default;
    }

    private function ai(): array
    {
        return $this->config()['ai'] ?? [];
    }

    private function config(): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        $path = base_path('config.php');

        if (!is_file($path)) {
            $this->config = [];
            return $this->config;
        }

        $config = require $path;

        $this->config = is_array($config) ? $config : [];

        return $this->config;
    }
}