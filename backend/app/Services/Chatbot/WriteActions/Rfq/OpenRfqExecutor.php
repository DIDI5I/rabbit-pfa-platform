<?php

namespace App\Services\Chatbot\WriteActions\Rfq;

use App\Core\Auth;
use App\Services\Chatbot\WriteActions\Contracts\PendingActionExecutorInterface;
use App\Services\RfqService;
use Throwable;

class OpenRfqExecutor implements PendingActionExecutorInterface
{
    public function supports(array $action): bool
    {
        return ($action['tool'] ?? null) === 'open_rfq';
    }

    public function execute(array $identity, array $action): array
    {
        $rfqId = (int) ($action['params']['rfq_id'] ?? 0);
        $decisionNote = $action['params']['decision_note'] ?? null;

        try {
            $result = (new RfqService())->openByData(
                $rfqId,
                $decisionNote,
                Auth::id()
            );

            return [
                'executed' => true,
                'answer' => "Confirmed. RFQ #{$rfqId} was opened.",
                'summary' => [
                    'updated' => true,
                    'action' => 'open_rfq',
                    'rfq_id' => $rfqId,
                    'service_message' => $result['message'] ?? null,
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'open_rfq',
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
                'answer' => "RFQ #{$rfqId} could not be opened.",
                'summary' => [
                    'updated' => false,
                    'action' => 'open_rfq',
                    'rfq_id' => $rfqId,
                    'error' => $e->getMessage(),
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'open_rfq',
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