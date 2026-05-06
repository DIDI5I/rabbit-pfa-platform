<?php

namespace App\Services\Chatbot\Tools\Rfq;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\RfqService;
use Throwable;

class RfqsByStatusTool extends BaseToolHandler
{
    private const VALID_STATUSES = [
        'draft',
        'open',
        'quoted',
        'accepted',
        'rejected',
        'expired',
        'cancelled',
    ];

    public function supports(string $intent): bool
    {
        return $intent === 'rfqs_by_status';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $status = strtolower(trim((string) ($params['status'] ?? '')));

        if ($status === '' || !in_array($status, self::VALID_STATUSES, true)) {
            return $this->result(
                'rfqs_by_status',
                'missing_params',
                [
                    'missing_params' => ['status'],
                    'allowed_statuses' => self::VALID_STATUSES,
                ],
                $this->meta('rfqs_by_status', $role, 'read_only', false, 0),
                [
                    [
                        'code' => 'invalid_rfq_status',
                        'message' => 'A valid RFQ status is required.',
                    ],
                ]
            );
        }

        try {
            $response = (new RfqService())->list();
            $data = $response['data'] ?? $response;

            $items = $data['items']
                ?? $data['rfqs']
                ?? (is_array($data) ? $data : []);

            if (!is_array($items)) {
                $items = [];
            }

            $filtered = array_values(array_filter($items, function (array $item) use ($status) {
                return strtolower((string) ($item['status'] ?? '')) === $status;
            }));

            return $this->result(
                'rfqs_by_status',
                'success',
                [
                    'status' => $status,
                    'rfqs' => $filtered,
                    'count' => count($filtered),
                    'total_before_filter' => count($items),
                ],
                $this->meta('rfqs_by_status', $role, 'read_only', false, count($filtered))
            );
        } catch (Throwable $e) {
            return $this->result(
                'rfqs_by_status',
                'failed',
                [
                    'status' => $status,
                ],
                $this->meta('rfqs_by_status', $role, 'read_only', false, 0),
                [
                    [
                        'code' => 'rfqs_by_status_failed',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }
}