<?php

namespace App\Core;

use PDO;

class Database
{
    protected PDO $pdo;

    public function __construct(string $dsn, string $username, string $password)
    {
        $this->pdo = new PDO(
            $dsn,
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    public function connection(): PDO
    {
        return $this->pdo;
    }
}