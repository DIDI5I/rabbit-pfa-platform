<?php

namespace App\Support;

class ApiResponse
{
    public static function success(string $message, mixed $data = null): array
    {
        $response = [
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $response;
    }

    public static function error(string $message): array
    {
        return [
            'error' => $message
        ];
    }

    public static function validation(array $fields): array
    {
        return [
            'error' => 'Validation failed',
            'fields' => $fields
        ];
    }
}