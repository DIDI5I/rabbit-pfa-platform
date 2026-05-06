<?php

namespace App\Services\Chatbot\Tools\Rfq;

use App\Services\Chatbot\Tools\BaseToolHandler;
use App\Services\RfqService;
use Throwable;

class RfqDetailsTool extends BaseToolHandler
{
    public function supports(string $intent): bool
    {
        return $intent === 'rfq_details';
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';
        $rfqId = (int) ($params['rfq_id'] ?? 0);

        if ($rfqId <= 0) {
            return $this->result(
                'rfq_details',
                'missing_params',
                [
                    'missing_params' => ['rfq_id'],
                ],
                $this->meta('rfq_details', $role, 'read_only', false, 0),
                [
                    [
                        'code' => 'missing_rfq_id',
                        'message' => 'rfq_id is required.',
                    ],
                ]
            );
        }

        try {
            $response = (new RfqService())->detail($rfqId);
            $data = $response['data'] ?? $response;

            return $this->result(
                'rfq_details',
                'success',
                [
                    'rfq_id' => $rfqId,
                    'rfq' => $data,
                    'count' => $data ? 1 : 0,
                ],
                $this->meta('rfq_details', $role, 'read_only', false, $data ? 1 : 0)
            );
        } catch (Throwable $e) {
            return $this->result(
                'rfq_details',
                'failed',
                [
                    'rfq_id' => $rfqId,
                ],
                $this->meta('rfq_details', $role, 'read_only', false, 0),
                [
                    [
                        'code' => 'rfq_details_failed',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }
}