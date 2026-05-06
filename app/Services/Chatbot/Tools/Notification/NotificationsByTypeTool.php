<?php

namespace App\Services\Chatbot\Tools\Notification;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\NotificationService;
use Throwable;

class NotificationsByTypeTool extends BaseToolHandler
{
    private const TYPE_GROUPS = [
        'RFQ' => [
            'RFQ_ASSIGNED',
            'RFQ_QUOTED',
            'RFQ_ACCEPTED_BY_OWNER',
            'RFQ_REJECTED_BY_OWNER',
        ],
        'STOCK' => [
            'LOW_STOCK_ALERT',
            'OUT_OF_STOCK_ALERT',
        ],
        'PURCHASE_LOT' => [
            'PURCHASE_LOT_NEEDS_FINALIZATION',
            'PURCHASE_LOT_FINALIZED',
        ],
        'ORDER' => [
            'ORDER_CREATED',
            'ORDER_STATUS_CHANGED',
        ],
    ];

    public function supports(string $intent): bool
    {
        return $intent === 'notifications_by_type';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $type = strtoupper(trim((string) ($params['type'] ?? '')));

        if ($type === '') {
            return $this->result(
                'notifications_by_type',
                'missing_params',
                [
                    'missing_params' => ['type'],
                ],
                $this->meta('notifications_by_type', $role, 'read_only', false, 0),
                [
                    [
                        'code' => 'missing_notification_type',
                        'message' => 'type is required.',
                    ],
                ]
            );
        }

        try {
            $response = (new NotificationService())->list([
                'page' => 1,
                'limit' => 100,
            ]);

            $data = $response['data'] ?? [];
            $items = $data['items'] ?? [];

            if (!is_array($items)) {
                $items = [];
            }

            $allowedTypes = self::TYPE_GROUPS[$type] ?? [$type];

            $filtered = array_values(array_filter($items, function (array $item) use ($allowedTypes) {
                return in_array((string) ($item['type'] ?? ''), $allowedTypes, true);
            }));

            $preview = array_slice($filtered, 0, 5);

            return $this->result(
                'notifications_by_type',
                'success',
                [
                    'type' => $type,
                    'matched_types' => $allowedTypes,
                    'notifications' => $preview,
                    'total' => count($filtered),
                    'count' => count($preview),
                    'unread_count' => (int) ($data['unread_count'] ?? 0),
                ],
                $this->meta('notifications_by_type', $role, 'read_only', false, count($preview))
            );
        } catch (Throwable $e) {
            return $this->result(
                'notifications_by_type',
                'failed',
                [
                    'type' => $type,
                ],
                $this->meta('notifications_by_type', $role, 'read_only', false, 0),
                [
                    [
                        'code' => 'notifications_by_type_failed',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }
}