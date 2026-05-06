<?php

namespace App\Repositories;

use App\Queries\RfqQuery;
use App\Exceptions\ValidationException;

class RfqRepository extends Repository
{
    public function findById(int $rfqId): ?array
    {
        return $this
            ->query(RfqQuery::findById(), [
                'id' => $rfqId
            ])
            ->fetchOne();
    }

    public function accept(int $rfqId, int $ownerUserId, ?string $note = null): array
    {
        $rfq = $this->findById($rfqId);

        if (!$rfq) {
        throw new ValidationException([
                    'rfq' => ['RFQ not found']
                ]);
            }

        if ($rfq['status'] !== 'quoted') {
            throw new ValidationException([
                    'rfq' => ["Only quoted RFQs can be accepted. Current status: {$rfq['status']}"]
                ]);
        }

        $this->query(RfqQuery::accept(), [
            'id' => $rfqId,
            'decision_note' => $note,
            'decided_by' => $ownerUserId
        ]);

        return [
            'rfq_id' => $rfqId,
            'previous_status' => 'quoted',
            'new_status' => 'accepted',
            'quoted_price' => isset($rfq['quoted_price']) ? (float) $rfq['quoted_price'] : null,
            'revision_id' => isset($rfq['revision_id']) ? (int) $rfq['revision_id'] : null,
            'decision_note' => $note,
            'decided_by' => $ownerUserId
        ];
    }

    public function reject(int $rfqId, int $ownerUserId, ?string $note = null): array
    {
        $rfq = $this->findById($rfqId);

        if (!$rfq) {
            throw new ValidationException([
                'rfq' => ['RFQ not found']
            ]);
        }

        if ($rfq['status'] !== 'quoted') {
            throw new ValidationException([
                'rfq' => ["Only quoted RFQs can be rejected. Current status: {$rfq['status']}"]
            ]);
        }

        $this->query(RfqQuery::reject(), [
            'id' => $rfqId,
            'decision_note' => $note,
            'decided_by' => $ownerUserId
        ]);

        return [
            'rfq_id' => $rfqId,
            'previous_status' => 'quoted',
            'new_status' => 'rejected',
            'decision_note' => $note,
            'decided_by' => $ownerUserId
        ];
    }

    public function expire(int $rfqId, int $userId, ?string $note = null): array
    {
        $rfq = $this->findById($rfqId);

        if (!$rfq) {
            throw new ValidationException([
                'rfq' => ['RFQ not found']
            ]);
        }

        if (!in_array($rfq['status'], ['quoted', 'open'], true)) {
            throw new ValidationException([
                'rfq' => ["Only quoted or open RFQs can be expired. Current status: {$rfq['status']}"]
            ]);
        }

        $this->query(RfqQuery::expire(), [
            'id' => $rfqId,
            'decision_note' => $note,
            'decided_by' => $userId
        ]);

        return [
            'rfq_id' => $rfqId,
            'previous_status' => $rfq['status'],
            'new_status' => 'expired',
            'decision_note' => $note,
            'decided_by' => $userId
        ];
    }

    public function open(int $rfqId): array
    {
        $rfq = $this->findById($rfqId);

        if (!$rfq) {
            throw new ValidationException([
                'rfq' => ['RFQ not found']
            ]);
        }

        if ($rfq['status'] !== 'draft') {
            throw new ValidationException([
                'rfq' => ["Only draft RFQs can be opened. Current status: {$rfq['status']}"]
            ]);
        }

        $this->query(RfqQuery::open(), [
            'id' => $rfqId
        ]);

        return [
            'rfq_id' => $rfqId,
            'previous_status' => 'draft',
            'new_status' => 'open'
        ];
    }

    public function findActiveForProduct(int $componentId): ?array
    {
        return $this
            ->query(RfqQuery::findActiveForProduct(), [
                'component_id' => $componentId
            ])
            ->fetchOne();
    }

    public function createAutoDraft(
        int $componentId,
        float $quantity,
        int $createdBy,
        ?int $supplierId
    ): int {
        $this->query(RfqQuery::createAutoDraft(), [
            'client_id' => $createdBy,
            'component_id' => $componentId,
            'supplier_id' => $supplierId,
            'quantity_requested' => $quantity,
            'client_message' => 'Auto-triggered draft RFQ created because stock dropped below threshold.'
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findPreferredSupplierForComponent(int $componentId): ?int
    {
        $result = $this
            ->query(RfqQuery::findPreferredSupplier(), [
                'component_id' => $componentId
            ])
            ->fetchOne();

        return $result ? (int) $result['supplier_id'] : null;
    }

    public function create(array $data): int
    {
        $this->query(RfqQuery::create(), [
            'client_id' => $data['created_by'],
            'component_id' => $data['component_id'],
            'supplier_id' => $data['supplier_id'],
            'quantity_requested' => $data['quantity_requested'],
            'status' => $data['status'],
            'quoted_price' => $data['quoted_price'],
            'auto_triggered' => $data['auto_triggered'],
            'decision_note' => $data['decision_note'],
            'client_message' => $data['client_message'] ?? null,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function listForOwner(): array
    {
        $this->query(RfqQuery::listForOwner());
        return $this->fetchMany();
    }

    public function listForSupplier(int $supplierId): array
    {
        $this->query(
            RfqQuery::listForSupplier(),
            ['supplier_id' => $supplierId]
        );

        return $this->fetchMany();
    }

    public function findDetailById(int $id): ?array
    {
        $this->query(
            RfqQuery::findDetailById(),
            ['id' => $id]
        );

        return $this->fetchOne() ?: null;
    }

    public function updateQuote(int $id, float $quotedPrice): bool
    {
        $this->query(RfqQuery::updateQuote(), [
            'id' => $id,
            'quoted_price' => $quotedPrice
        ]);

        return true;
    }
    
    
}