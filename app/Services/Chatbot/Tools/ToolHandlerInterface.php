<?php

namespace App\Services\Chatbot\Tools;

interface ToolHandlerInterface
{
    public function supports(string $intent): bool;

    public function execute(string $intent, array $params, array $identity, array $context = []): array;
}