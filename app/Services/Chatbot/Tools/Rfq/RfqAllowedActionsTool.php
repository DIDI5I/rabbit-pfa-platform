<?php

namespace App\Services\Chatbot\Tools\Rfq;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\RfqService;
use Throwable;

class RfqAllowedActionsTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'rfq_allowed_actions';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $rfqId = (int) ($params['rfq_id'] ?? 0);

        if ($rfqId <= 0) {
            return $this->result(
                'rfq_allowed_actions',
                'missing_params',
                [
                    'missing_params' => ['rfq_id'],
                ],
                $this->meta('rfq_allowed_actions', $role, 'read_only', false, 0),
                [
                    [
                        'code' => 'missing_rfq_id',
                        'message' => 'rfq_id is required.',
                    ],
                ]
            );
        }

        try {
            $response = (new RfqService())->getAllowedActions($rfqId);
            $data = $response['data'] ?? $response;

            $actions = $data['allowed_actions']
                ?? $data['actions']
                ?? [];

            if (!is_array($actions)) {
                $actions = [];
            }

            return $this->result(
                'rfq_allowed_actions',
                'success',
                [
                    'rfq_id' => $rfqId,
                    'allowed_actions' => $actions,
                    'raw' => $data,
                    'count' => count($actions),
                ],
                $this->meta('rfq_allowed_actions', $role, 'read_only', false, count($actions))
            );
        } catch (Throwable $e) {
            return $this->result(
                'rfq_allowed_actions',
                'failed',
                [
                    'rfq_id' => $rfqId,
                ],
                $this->meta('rfq_allowed_actions', $role, 'read_only', false, 0),
                [
                    [
                        'code' => 'rfq_allowed_actions_failed',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }
}