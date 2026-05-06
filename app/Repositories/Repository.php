<?php

namespace App\Repositories;

use App\Core\App;
use App\Core\Database;
use PDO;
use PDOStatement;

class Repository
{
    protected PDO $connection;
    protected ?PDOStatement $statement = null;

    public function __construct()
    {
        $this->connection = App::resolve(Database::class)->connection();
    }

    public function query(string $query, array $params = []): static
    {
        $this->statement = $this->connection->prepare($query);
        
        $this->statement->execute($params);

        return $this;
    }

    public function exists(): bool
    {
        return (bool) $this->statement?->fetch();
    }

    public function fetchOne(): ?array
    {
        $result = $this->statement?->fetch();

        return $result ?: null;
    }

    public function fetchMany(): array
    {
        return $this->statement?->fetchAll() ?: [];
    }

    public function lastInsertId(): int
    {
        return (int) $this->connection->lastInsertId();
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollBack(): void
    {
        if ($this->connection->inTransaction()) {
            $this->connection->rollBack();
        }
    }
}