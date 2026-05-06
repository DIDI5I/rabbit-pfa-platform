<?php

namespace App\Forms;

use App\Support\Validator;

class StoreCatalogReviewRequest
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(): void
    {
        Validator::make($this->data)
            ->required('user_id')
            ->numeric('user_id')
            ->required('rating')
            ->numeric('rating')
            ->validate();

        $rating = $this->rating();

        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5.');
        }
    }

    public function userId(): int
    {
        return (int) $this->data['user_id'];
    }

    public function rating(): int
    {
        return (int) $this->data['rating'];
    }

    public function title(): ?string
    {
        $value = trim((string) ($this->data['title'] ?? ''));

        return $value !== '' ? $value : null;
    }

    public function comment(): ?string
    {
        $value = trim((string) ($this->data['comment'] ?? ''));

        return $value !== '' ? $value : null;
    }
}