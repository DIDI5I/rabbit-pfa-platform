<?php

namespace App\Services\Chatbot\WriteActions\Rfq;

use App\Core\Auth;
use App\Services\Chatbot\WriteActions\Contracts\PendingActionExecutorInterface;
use App\Services\RfqService;
use Throwable;

class ExpireRfqExecutor implements PendingActionExecutorInterface
{
    public function supports(array $action): bool
    {
        return ($action['tool'] ?? null) === 'expire_rfq';
    }

    public function execute(array $identity, array $action): array
    {
        $rfqId = (int) ($action['params']['rfq_id'] ?? 0);
        $decisionNote = $action['params']['decision_note'] ?? null;

        try {
            $result = (new RfqService())->expireByData(
                $rfqId,
                $decisionNote,
                Auth::id()
            );

            return [
                'executed' => true,
                'answer' => "Confirmed. RFQ #{$rfqId} was expired.",
                'summary' => [
                    'updated' => true,
                    'action' => 'expire_rfq',
                    'rfq_id' => $rfqId,
                    'service_message' => $result['message'] ?? null,
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'expire_rfq',
                        'status' => 'executed',
                    ],
                ],
                'limitations' => [],
                'suggested_actions' => [
                    'Show RFQ details',
                    'Show RFQs',
                ],
            ];
        } catch (Throwable $e) {
            return [
                'executed' => false,
                'answer' => "RFQ #{$rfqId} could not be expired.",
                'summary' => [
                    'updated' => false,
                    'action' => 'expire_rfq',
                    'rfq_id' => $rfqId,
                    'error' => $e->getMessage(),
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'expire_rfq',
                        'status' => 'execution_failed',
                    ],
                ],
                'limitations' => [
                    'The RFQ was not updated.',
                ],
                'suggested_actions' => [
                    'Show RFQ details',
                    'Show RFQ allowed actions',
                ],
            ];
        }
    }
}