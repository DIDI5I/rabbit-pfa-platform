<?php

namespace App\Forms\RFQs;

use App\Exceptions\ValidationException;

class QuoteRfqRequest
{
    protected array $data;

    protected int $userId;
    protected int $supplierCompanyId;

    public float $quoted_price;
    public ?int $lead_time_days;
    public ?string $supplier_note;

    public function __construct()
    {
        $this->authorize();

        $this->data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($this->data)) {
            throw new ValidationException([
                'body' => ['Invalid JSON body.']
            ]);
        }

        $this->validate();

        $this->userId = (int) $_SESSION['user_id'];
        $this->supplierCompanyId = (int) $_SESSION['supplier_company_id'];

        $this->quoted_price = (float) $this->data['quoted_price'];

        $this->lead_time_days = isset($this->data['lead_time_days']) && $this->data['lead_time_days'] !== ''
            ? (int) $this->data['lead_time_days']
            : null;

        $this->supplier_note = isset($this->data['supplier_note']) && trim((string) $this->data['supplier_note']) !== ''
            ? trim((string) $this->data['supplier_note'])
            : null;
    }

    protected function authorize(): void
    {
        if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
            throw new ValidationException([
                'auth' => ['Unauthorized']
            ]);
        }

        if ($_SESSION['role'] !== 'fournisseur') {
            throw new ValidationException([
                'auth' => ['Only suppliers can quote RFQs']
            ]);
        }

        if (empty($_SESSION['supplier_company_id'])) {
            throw new ValidationException([
                'auth' => ['Supplier company is missing from session']
            ]);
        }
    }

    protected function validate(): void
    {
        $errors = [];

        if (!isset($this->data['quoted_price'])) {
            $errors['quoted_price'][] = 'Quoted price is required.';
        } elseif (!is_numeric($this->data['quoted_price']) || (float) $this->data['quoted_price'] <= 0) {
            $errors['quoted_price'][] = 'Quoted price must be greater than 0.';
        }

        if (isset($this->data['lead_time_days']) && $this->data['lead_time_days'] !== '') {
            if (!filter_var($this->data['lead_time_days'], FILTER_VALIDATE_INT) || (int) $this->data['lead_time_days'] < 0) {
                $errors['lead_time_days'][] = 'Lead time days must be a positive integer.';
            }
        }

        if (isset($this->data['supplier_note']) && strlen((string) $this->data['supplier_note']) > 1000) {
            $errors['supplier_note'][] = 'Supplier note must not exceed 1000 characters.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function supplierCompanyId(): int
    {
        return $this->supplierCompanyId;
    }

    public function quotedPrice(): float
    {
        return $this->quoted_price;
    }

    public function leadTimeDays(): ?int
    {
        return $this->lead_time_days;
    }

    public function supplierNote(): ?string
    {
        return $this->supplier_note;
    }
}