<?php

namespace App\Services\Chatbot;

class ChatbotLogger
{
    private string $logFile;

    public function __construct()
    {
        $this->logFile = dirname(__DIR__, 3) . '/storage/logs/chatbot.log';
    }

    public function log(array $payload): void
    {
        $this->ensureLogDirectoryExists();

        $entry = [
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => $payload['user_id'] ?? null,
            'role' => $payload['role'] ?? 'guest',
            'message' => $payload['message'] ?? null,
            'intent' => $payload['intent'] ?? null,
            'tool' => $payload['tool'] ?? null,
            'status' => $payload['status'] ?? null,
            'operation_type' => $payload['operation_type'] ?? null,
            'permission_status' => $payload['permission_status'] ?? null,
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