<?php

namespace App\Forms\RFQs;

use App\Exceptions\ValidationException;

class AcceptRfqRequest
{
    protected array $data;
    protected int $rfqId;
    protected ?string $decisionNote;
    protected int $userId;

    public function __construct()
    {
        $this->data = json_decode(file_get_contents('php://input'), true) ?? [];

        $this->authorize();
        $this->validate();

        $this->rfqId = (int) $this->data['rfq_id'];
        $this->decisionNote = isset($this->data['decision_note'])
            ? trim((string) $this->data['decision_note'])
            : null;

        $this->userId = (int) $_SESSION['user_id'];
    }

    protected function authorize(): void
    {
        if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
            throw new ValidationException([
                'auth' => ['Unauthorized']
            ]);
        }

        if ($_SESSION['role'] !== 'owner') {
            throw new ValidationException([
                'auth' => ['Only owners can accept RFQs']
            ]);
        }
    }

    protected function validate(): void
    {
        if (!isset($this->data['rfq_id'])) {
            throw new ValidationException([
                'rfq_id' => ['Missing required field: rfq_id']
            ]);
        }

        if ((int) $this->data['rfq_id'] <= 0) {
            throw new ValidationException([
                'rfq_id' => ['Invalid rfq_id']
            ]);
        }
    }

    public function rfqId(): int
    {
        return $this->rfqId;
    }

    public function decisionNote(): ?string
    {
        return $this->decisionNote;
    }

    public function userId(): int
    {
        return $this->userId;
    }
}