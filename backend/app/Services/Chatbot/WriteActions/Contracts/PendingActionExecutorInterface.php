<?php

namespace App\Services\Chatbot\WriteActions\Contracts;

interface PendingActionExecutorInterface
{
    public function supports(array $action): bool;

    public function execute(array $identity, array $action): array;
}