<?php

namespace App\Core;

use App\Exceptions\ValidationException;
use Exception;

class Response
{
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');

        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    public static function success($data = [], int $status = Status::OK): void
    {
        self::json($data, $status);
    }

    public static function handleException(Exception $e): void
        {
            if ($e instanceof \App\Exceptions\ValidationException) {
                self::error(
                    $e->getMessage(),
                    Status::UNPROCESSABLE_ENTITY,
                    ['fields' => $e->fields()]
                );
                return;
            }

            $code = $e->getCode();

            $status = is_int($code) && $code >= 100 && $code <= 599
                ? $code
                : Status::INTERNAL_SERVER_ERROR;

            self::error($e->getMessage(), $status);
}

    public static function error(string $message, int $status = 500, array $extra = []): void
        {
            self::json(array_merge([
                'error' => $message,
            ], $extra), $status);
        }
}