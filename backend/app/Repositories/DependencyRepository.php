<?php

namespace App\Repositories;

use App\Queries\DependencyQuery;

class DependencyRepository extends Repository
{
    public function findChildren(int $productId): array
    {
        $rows = $this
            ->query(DependencyQuery::findChildren(), [$productId])
            ->fetchMany();

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['parent_id'] = (int) $row['parent_id'];
            $row['child_id'] = (int) $row['child_id'];
            $row['qty_required'] = (float) $row['qty_required'];
            $row['is_phantom'] = (bool) $row['is_phantom'];
        }

        unset($row);

        return $rows;
    }

    public function relationshipExists(int $parentId, int $childId, string $relationType): bool
    {
        return $this
            ->query(
                DependencyQuery::existsSameRelation(),
                [$parentId, $childId, $relationType]
            )
            ->exists();
    }

    public function findByIdForParent(int $parentId, int $dependencyId): ?array
    {
        $row = $this
            ->query(
                DependencyQuery::findByIdForParent(),
                [$parentId, $dependencyId]
            )
            ->fetchOne();

        if (!$row) {
            return null;
        }

        $row['id'] = (int) $row['id'];
        $row['parent_id'] = (int) $row['parent_id'];
        $row['child_id'] = (int) $row['child_id'];
        $row['qty_required'] = (float) $row['qty_required'];
        $row['is_phantom'] = (bool) $row['is_phantom'];

        return $row;
    }

    public function create(
        int $parentId,
        int $childId,
        string $relationType,
        float $qtyRequired,
        string $uom,
        bool $isPhantom,
        ?string $notes
    ): void {
        $this->query(
            DependencyQuery::insert(),
            [
                $parentId,
                $childId,
                $relationType,
                $qtyRequired,
                $uom,
                $isPhantom ? 1 : 0,
                $notes,
            ]
        );
    }

    public function delete(int $parentId, int $dependencyId): void
    {
        $this->query(
            DependencyQuery::delete(),
            [$parentId, $dependencyId]
        );
    }

    public function update(int $parentId, int $dependencyId, array $data): void
    {
        if (empty($data)) {
            return;
        }

        if (isset($data['is_phantom'])) {
            $data['is_phantom'] = $data['is_phantom'] ? 1 : 0;
        }

        $this->query(
            DependencyQuery::update(array_keys($data)),
            [...array_values($data), $parentId, $dependencyId]
        );
    }

    public function wouldCreateCycle(int $parentId, int $childId): bool
    {
        return $this
            ->query(DependencyQuery::wouldCreateCycle(), [$childId, $parentId])
            ->exists();
    }
}