<?php

namespace App\Repositories;

use App\Queries\PurchaseLotQuery;

class PurchaseLotRepository extends Repository
{
    public function create(array $data): int
    {
       $this->query(PurchaseLotQuery::insert(), [
            $data['component_id'],
            $data['supplier_id'],
            $data['quantity_received'],
            $data['supplier_unit_price'],
            $data['supplier_total_price'],
            $data['transport_cost'],
            $data['customs_cost'],
            $data['handling_cost'],
            $data['packaging_cost'],
            $data['order_preparation_cost'],
            $data['other_cost'],
            $data['total_purchase_cost'],
            $data['unit_purchase_cost'],
            $data['status'],
            $data['purchase_date'],
            $data['reference_type'],
            $data['reference_id'],
            $data['notes'],
            $data['created_by'],
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function findAll(): array
    {
        $rows = $this->query(PurchaseLotQuery::findAll())->fetchMany();

        return $this->castRows($rows);
    }

    public function findByComponent(int $componentId): array
    {
        $rows = $this
            ->query(PurchaseLotQuery::findByComponent(), [$componentId])
            ->fetchMany();

        return $this->castRows($rows);
    }

    public function findById(int $id): ?array
    {
        $row = $this
            ->query(PurchaseLotQuery::findById(), [$id])
            ->fetchOne();

        if (!$row) {
            return null;
        }

        return $this->castRow($row);
    }

    public function existsByReference(string $referenceType, int $referenceId): bool
    {
        return $this
            ->query(PurchaseLotQuery::existsByReference(), [$referenceType, $referenceId])
            ->exists();
    }

    private function castRows(array $rows): array
    {
        foreach ($rows as &$row) {
            $row = $this->castRow($row);
        }

        unset($row);

        return $rows;
    }

    private function castRow(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['component_id'] = (int) $row['component_id'];
        $row['supplier_id'] = (int) $row['supplier_id'];

        $row['quantity_received'] = (float) $row['quantity_received'];
        $row['supplier_unit_price'] = (float) $row['supplier_unit_price'];
        $row['supplier_total_price'] = (float) $row['supplier_total_price'];

        $row['transport_cost'] = (float) $row['transport_cost'];
        $row['customs_cost'] = (float) $row['customs_cost'];
        $row['handling_cost'] = (float) $row['handling_cost'];
        $row['packaging_cost'] = (float) $row['packaging_cost'];
        $row['order_preparation_cost'] = (float) $row['order_preparation_cost'];
        $row['other_cost'] = (float) $row['other_cost'];

        $row['total_purchase_cost'] = (float) $row['total_purchase_cost'];
        $row['unit_purchase_cost'] = (float) $row['unit_purchase_cost'];
        $row['status'] = (string) $row['status'];

        $row['reference_id'] = $row['reference_id'] !== null
            ? (int) $row['reference_id']
            : null;

        $row['created_by'] = $row['created_by'] !== null
            ? (int) $row['created_by']
            : null;

        return $row;
    }

    public function updateCostsAndStatus(int $id, array $data): void
    {
        $this->query(
            PurchaseLotQuery::updateCostsAndStatus(),
            [
                $data['transport_cost'],
                $data['customs_cost'],
                $data['handling_cost'],
                $data['packaging_cost'],
                $data['order_preparation_cost'],
                $data['other_cost'],
                $data['total_purchase_cost'],
                $data['unit_purchase_cost'],
                $data['status'],
                $id,
            ]
        );
    }

    public function hasPurchaseReceivedStockMovement(int $purchaseLotId): bool
    {
        return $this
            ->query(PurchaseLotQuery::existsStockMovementForPurchaseLot(), [$purchaseLotId])
            ->exists();
    }
}