<?php

namespace App\Services\Chatbot;

class OperationClassifier
{
    public function classify(string $intent, ?array $toolDefinition): string
    {
        if ($intent === 'unknown' || !$toolDefinition) {
            return 'unsupported';
        }

        return $toolDefinition['operation_type'] ?? 'unsupported';
    }
}