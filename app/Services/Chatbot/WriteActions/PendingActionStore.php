<?php

namespace App\Services\Chatbot\WriteActions;

class PendingActionStore
{
    private const SESSION_KEY = 'chatbot_pending_action';

    public function put(array $identity, array $action): void
    {
        $_SESSION[self::SESSION_KEY] = [
            'created_at' => time(),
            'user_id' => $identity['user_id'] ?? null,
            'role' => $identity['role'] ?? 'guest',

            'action_id' => $this->generateActionId(),
            'intent' => $action['intent'] ?? null,
            'tool' => $action['tool'] ?? null,
            'operation_type' => 'write_action',

            'title' => $action['title'] ?? 'Pending action',
            'description' => $action['description'] ?? null,

            'params' => $action['params'] ?? [],
            'preview' => $action['preview'] ?? [],
            'risk_level' => $action['risk_level'] ?? 'medium',

            'expires_at' => time() + ($action['ttl_seconds'] ?? 300),
        ];
    }

    public function get(array $identity): ?array
    {
        $action = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_array($action)) {
            return null;
        }

        if ($this->isExpired($action)) {
            $this->clear();
            return null;
        }

        if (!$this->belongsToIdentity($action, $identity)) {
            return null;
        }

        return $action;
    }

    public function clear(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    public function has(array $identity): bool
    {
        return $this->get($identity) !== null;
    }

    private function belongsToIdentity(array $action, array $identity): bool
    {
        $actionRole = $action['role'] ?? 'guest';
        $currentRole = $identity['role'] ?? 'guest';

        if ($actionRole !== $currentRole) {
            return false;
        }

        $actionUserId = $action['user_id'] ?? null;
        $currentUserId = $identity['user_id'] ?? null;

        return (string) $actionUserId === (string) $currentUserId;
    }

    private function isExpired(array $action): bool
    {
        return time() > (int) ($action['expires_at'] ?? 0);
    }

    private function generateActionId(): string
    {
        return bin2hex(random_bytes(8));
    }
}