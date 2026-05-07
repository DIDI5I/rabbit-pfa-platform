<?php

namespace App\Services\Chatbot\WriteActions\Rfq;

use App\Core\Auth;
use App\Services\Chatbot\WriteActions\Contracts\PendingActionExecutorInterface;
use App\Services\RfqService;
use Throwable;

class AcceptRfqExecutor implements PendingActionExecutorInterface
{
    public function supports(array $action): bool
    {
        return ($action['tool'] ?? null) === 'accept_rfq';
    }

    public function execute(array $identity, array $action): array
    {
        $rfqId = (int) ($action['params']['rfq_id'] ?? 0);
        $decisionNote = $action['params']['decision_note'] ?? null;

        try {
            $result = (new RfqService())->acceptByData(
                $rfqId,
                $decisionNote,
                Auth::id()
            );

            return [
                'executed' => true,
                'answer' => "Confirmed. RFQ #{$rfqId} was accepted and a purchase lot was created.",
                'summary' => [
                    'updated' => true,
                    'action' => 'accept_rfq',
                    'rfq_id' => $rfqId,
                    'purchase_lot_created' => true,
                    'service_message' => $result['message'] ?? null,
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'accept_rfq',
                        'status' => 'executed',
                    ],
                ],
                'limitations' => [],
                'suggested_actions' => [
                    'Show RFQ details',
                    'Show purchase lots',
                    'Show RFQs',
                ],
            ];
        } catch (Throwable $e) {
            return [
                'executed' => false,
                'answer' => "RFQ #{$rfqId} could not be accepted.",
                'summary' => [
                    'updated' => false,
                    'action' => 'accept_rfq',
                    'rfq_id' => $rfqId,
                    'purchase_lot_created' => false,
                    'error' => $e->getMessage(),
                ],
                'items_preview' => [],
                'sources' => [
                    [
                        'tool' => 'accept_rfq',
                        'status' => 'execution_failed',
                    ],
                ],
                'limitations' => [
                    'The RFQ was not updated and no purchase lot was created.',
                ],
                'suggested_actions' => [
                    'Show RFQ details',
                    'Show RFQ allowed actions',
                ],
            ];
        }
    }
}