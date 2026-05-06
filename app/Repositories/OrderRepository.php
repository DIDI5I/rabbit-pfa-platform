<?php

namespace App\Repositories;

use App\Queries\OrderQuery;

class OrderRepository extends Repository
{
    public function createOrder(array $data): int
    {
        $this->query(
            OrderQuery::insertOrder(),
            [
                $data['client_id'],
                $data['total_amount'],
                $data['shipping_address'] ?? null,
                $data['notes'] ?? null,
            ]
        );

        return (int) $this->connection->lastInsertId();
    }

    public function createOrderItem(int $orderId, array $item): void
    {
        $this->query(
            OrderQuery::insertOrderItem(),
            [
                $orderId,
                $item['component_id'],
                $item['quantity'],
                $item['unit_price'],
                $item['supplier_id'] ?? null,
            ]
        );
    }

    public function findAll(): array
    {
        $rows = $this
            ->query(OrderQuery::findAll())
            ->fetchMany();

        foreach ($rows as &$row) {
            $row = $this->castOrder($row);
        }

        unset($row);

        return $rows;
    }

    public function findById(int $orderId): ?array
    {
        $row = $this
            ->query(OrderQuery::findById(), [$orderId])
            ->fetchOne();

        return $row ? $this->castOrder($row) : null;
    }

    public function findItemsByOrderId(int $orderId): array
    {
        $rows = $this
            ->query(OrderQuery::findItemsByOrderId(), [$orderId])
            ->fetchMany();

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['order_id'] = (int) $row['order_id'];
            $row['component_id'] = (int) $row['component_id'];
            $row['quantity'] = (float) $row['quantity'];
            $row['unit_price'] = (float) $row['unit_price'];
            $row['line_total'] = (float) $row['line_total'];
            $row['supplier_id'] = $row['supplier_id'] !== null
                ? (int) $row['supplier_id']
                : null;
        }

        unset($row);

        return $rows;
    }

    public function updateStatus(int $orderId, string $status): void
    {
        $this->query(
            OrderQuery::updateStatus(),
            [$status, $orderId]
        );
    }

    public function hasSaleStockMovement(int $orderId): bool
    {
        return $this
            ->query(OrderQuery::findExistingStockMovementForOrder(), [$orderId])
            ->exists();
    }

    public function findPreferredUnitCost(int $componentId): ?float
    {
        $row = $this
            ->query(OrderQuery::findProductPrice(), [$componentId])
            ->fetchOne();

        if (!$row || $row['unit_cost'] === null) {
            return null;
        }

        return (float) $row['unit_cost'];
    }

    private function castOrder(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['client_id'] = (int) $row['client_id'];
        $row['total_amount'] = $row['total_amount'] !== null
            ? (float) $row['total_amount']
            : 0.0;

        return $row;
    }

    public function hasRestoreStockMovement(int $orderId): bool
    {
        return $this
            ->query(OrderQuery::findExistingRestoreMovementForOrder(), [$orderId])
            ->exists();
    }
}