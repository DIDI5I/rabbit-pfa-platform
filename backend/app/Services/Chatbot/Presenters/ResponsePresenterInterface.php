<?php

namespace App\Services\Chatbot\Presenters;

interface ResponsePresenterInterface
{
    public function supports(string $tool): bool;

    public function present(array $toolResult, array $identity): array;
}