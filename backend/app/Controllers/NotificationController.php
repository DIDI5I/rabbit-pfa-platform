<?php

namespace App\Controllers;

use App\Services\NotificationService;

class NotificationController
{
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    public function index(): array
    {
        return $this->notificationService->list($_GET);
    }

    public function unreadCount(): array
    {
        return $this->notificationService->unreadCount();
    }

    public function markAsRead(int $id): array
    {
        return $this->notificationService->markAsRead($id);
    }

    public function markAllAsRead(): array
    {
        return $this->notificationService->markAllAsRead();
    }
}