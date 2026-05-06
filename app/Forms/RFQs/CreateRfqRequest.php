<?php

namespace App\Forms\RFQs;

use App\Exceptions\ValidationException;

class CreateRfqRequest
{
    public int $component_id;
    public ?int $supplier_id;
    public float $quantity_requested;

    public function __construct()
    {
        $this->authorize();

        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            throw new ValidationException([
                'body' => ['Invalid JSON body.']
            ]);
        }

        $errors = [];

        if (empty($data['component_id'])) {
            $errors['component_id'][] = 'Component ID is required.';
        } elseif (!filter_var($data['component_id'], FILTER_VALIDATE_INT)) {
            $errors['component_id'][] = 'Component ID must be an integer.';
        }

        if (isset($data['supplier_id']) && $data['supplier_id'] !== null && $data['supplier_id'] !== '') {
            if (!filter_var($data['supplier_id'], FILTER_VALIDATE_INT)) {
                $errors['supplier_id'][] = 'Supplier ID must be an integer.';
            }
        }

        if (!isset($data['quantity_requested'])) {
            $errors['quantity_requested'][] = 'Quantity requested is required.';
        } elseif (!is_numeric($data['quantity_requested']) || (float) $data['quantity_requested'] <= 0) {
            $errors['quantity_requested'][] = 'Quantity requested must be greater than 0.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->component_id = (int) $data['component_id'];

        $this->supplier_id = isset($data['supplier_id']) && $data['supplier_id'] !== ''
            ? (int) $data['supplier_id']
            : null;

        $this->quantity_requested = (float) $data['quantity_requested'];
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
                'auth' => ['Only owners can create RFQs']
            ]);
        }
    }
}