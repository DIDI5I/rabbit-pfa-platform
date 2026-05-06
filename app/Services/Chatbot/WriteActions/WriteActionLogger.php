<?php

namespace App\Services\Chatbot\WriteActions;

class WriteActionLogger
{
    private string $logFile;

    public function __construct()
    {
        $this->logFile = dirname(__DIR__, 4) . '/storage/logs/chatbot_write_actions.log';
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
            'action_id' => $payload['action_id'] ?? null,

            'status' => $payload['status'] ?? null,
            'risk_level' => $payload['risk_level'] ?? null,

            'confirmed' => (bool) ($payload['confirmed'] ?? false),
            'executed' => (bool) ($payload['executed'] ?? false),
            'cancelled' => (bool) ($payload['cancelled'] ?? false),

            'params_summary' => $this->sanitizeParams($payload['params_summary'] ?? []),
            'error' => $payload['error'] ?? null,
        ];

        file_put_contents(
            $this->logFile,
            json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND
        );
    }

    private function sanitizeParams(mixed $params): array
    {
        if (!is_array($params)) {
            return [];
        }

        $blockedKeys = [
            'password',
            'api_key',
            'token',
            'secret',
            'authorization',
            'cookie',
            'session',
        ];

        $clean = [];

        foreach ($params as $key => $value) {
            $keyString = strtolower((string) $key);

            foreach ($blockedKeys as $blockedKey) {
                if (str_contains($keyString, $blockedKey)) {
                    $clean[$key] = '[redacted]';
                    continue 2;
                }
            }

            if (is_scalar($value) || $value === null) {
                $clean[$key] = $value;
            } else {
                $clean[$key] = '[complex]';
            }
        }

        return $clean;
    }

    private function ensureLogDirectoryExists(): void
    {
        $directory = dirname($this->logFile);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }
}