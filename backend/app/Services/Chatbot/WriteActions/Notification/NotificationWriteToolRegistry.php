<?php

namespace App\Services\Chatbot\WriteActions\Notification;

class NotificationWriteToolRegistry
{
    public function tools(): array
    {
        return [
            'mark_all_notifications_read' => [
                'roles' => ['owner', 'client', 'supplier', 'fournisseur'],
                'tool' => 'mark_all_notifications_read',
                'operation_type' => 'write_action',
                'sensitive' => true,
                'required_params' => [],
            ],

            'mark_notification_read' => [
                'roles' => ['owner', 'client', 'supplier', 'fournisseur'],
                'tool' => 'mark_notification_read',
                'operation_type' => 'write_action',
                'sensitive' => true,
                'required_params' => ['notification_id'],
            ],
        ];
    }
}