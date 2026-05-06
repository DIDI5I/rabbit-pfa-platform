<?php

namespace App\Services\Chatbot\Ai;

class AiRefinementLogger
{
    private string $logFile;

    public function __construct()
    {
        $this->logFile = dirname(__DIR__, 4) . '/storage/logs/chatbot_ai.log';
    }

    public function log(array $payload): void
    {
        $this->ensureLogDirectoryExists();

        $entry = [
            'created_at' => date('Y-m-d H:i:s'),

            'user_id' => $payload['user_id'] ?? null,
            'role' => $payload['role'] ?? 'guest',

            'intent' => $payload['intent'] ?? null,
            'tool' => $payload['tool'] ?? null,

            'ai_refined' => (bool) ($payload['ai_refined'] ?? false),
            'provider' => $payload['provider'] ?? null,
            'model' => $payload['model'] ?? null,
            'prompt_version' => $payload['prompt_version'] ?? null,

            'fallback_reason' => $payload['fallback_reason'] ?? null,

            'input_char_count' => $payload['input_char_count'] ?? null,
            'output_char_count' => $payload['output_char_count'] ?? null,

            'validation_passed' => $payload['validation_passed'] ?? null,
            'validation_violations' => $payload['validation_violations'] ?? [],

            'error' => $payload['error'] ?? null,
        ];

        file_put_contents(
            $this->logFile,
            json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND
        );
    }

    private function ensureLogDirectoryExists(): void
    {
        $directory = dirname($this->logFile);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }
}