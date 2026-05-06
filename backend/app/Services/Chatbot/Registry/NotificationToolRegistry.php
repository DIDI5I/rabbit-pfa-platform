<?php

namespace App\Services\Chatbot\Registry;

class NotificationToolRegistry
{
    public function tools(): array
    {
        return [
            'notification_summary' => [
                'roles' => ['owner', 'client', 'supplier', 'fournisseur'],
                'tool' => 'notification_summary',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => [],
            ],

            'unread_notifications' => [
                'roles' => ['owner', 'client', 'supplier', 'fournisseur'],
                'tool' => 'unread_notifications',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => [],
            ],

            'notifications_by_type' => [
                'roles' => ['owner', 'client', 'supplier', 'fournisseur'],
                'tool' => 'notifications_by_type',
                'operation_type' => 'read_only',
                'sensitive' => false,
                'required_params' => ['type'],
            ],
        ];
    }
}