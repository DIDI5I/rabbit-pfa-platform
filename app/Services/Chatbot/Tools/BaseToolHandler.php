<?php

namespace App\Services\Chatbot\Tools;

abstract class BaseToolHandler implements ToolHandlerInterface
{
    protected function result(string $tool, string $status, array $data, array $meta, array $errors = []): array
    {
        return [
            'tool' => $tool,
            'status' => $status,
            'data' => $data,
            'meta' => $meta,
            'errors' => $errors,
        ];
    }

    protected function extractData(array $response)
    {
        return $response['data'] ?? $response;
    }

    protected function meta(string $intent, string $role, string $operationType = 'read_only', bool $sensitive = false, ?int $count = null): array
    {
        return [
            'intent' => $intent,
            'operation_type' => $operationType,
            'role_scope' => $role,
            'sensitive' => $sensitive,
            'count' => $count,
        ];
    }
}