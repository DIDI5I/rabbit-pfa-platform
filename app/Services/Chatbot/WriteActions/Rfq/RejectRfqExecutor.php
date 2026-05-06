<?php

namespace App\Services\Chatbot\WriteActions\Rfq;

use App\Core\Auth;
use App\Services\Chatbot\WriteActions\Contracts\PendingActionExecutorInterface;
use App\Services\RfqService;
use Throwable;

class RejectRfqExecutor implements PendingActionExecutorInterface
{
    public function supports(array $action): bool
    {
        return ($action['tool'] ?? null) === 'reject_rfq';
    }

    public function execute(array $identity, array $action): array
    {
        $rfqId = (int) ($action['params']['rfq_id'] ?? 0);
        $decisionNote = $action['params']['decision_note'] ?? null;

        try {
            $result = (new RfqService())->rejectByData(
                $rfqId,
                $decisionNote,
                Auth::id()
            );

            return [
                'executed' => true,
                'answer' => "Confirmed. RFQ #{$rfqId} was rejected.",
                'summary' => [
                    'updated' => true,
                    'action' => 'reject_rfq',
                    'rfq_id' => $rfqId,
                    'service_message' => $result['message'] ?? null,
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'reject_rfq',
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
                'answer' => "RFQ #{$rfqId} could not be rejected.",
                'summary' => [
                    'updated' => false,
                    'action' => 'reject_rfq',
                    'rfq_id' => $rfqId,
                    'error' => $e->getMessage(),
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'reject_rfq',
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