<?php

namespace App\Services\Chatbot\WriteActions;

use App\Services\Chatbot\WriteActions\Contracts\PendingActionExecutorInterface;
use App\Services\Chatbot\WriteActions\Notification\MarkAllNotificationsReadExecutor;
use App\Services\Chatbot\WriteActions\Notification\MarkNotificationReadExecutor;
use App\Services\Chatbot\WriteActions\Rfq\RejectRfqExecutor;
use App\Services\Chatbot\WriteActions\Rfq\AcceptRfqExecutor;
use App\Services\Chatbot\WriteActions\Rfq\ExpireRfqExecutor;
use App\Services\Chatbot\WriteActions\Rfq\OpenRfqExecutor;
use App\Services\Chatbot\WriteActions\Order\UpdateOrderStatusExecutor;
use App\Services\Chatbot\WriteActions\Stock\StockMovementExecutor;
use App\Services\Chatbot\WriteActions\Stock\SetStockLevelExecutor;
use App\Services\Chatbot\WriteActions\PurchaseLot\FinalizePurchaseLotExecutor;

class WriteActionExecutor
{
    /** @var PendingActionExecutorInterface[] */
    private array $executors;

    public function __construct()
    {
        $this->executors = [
            new MarkAllNotificationsReadExecutor(),
            new MarkNotificationReadExecutor(),
            new RejectRfqExecutor(),
            new AcceptRfqExecutor(),
            new ExpireRfqExecutor(),
            new OpenRfqExecutor(),
            new UpdateOrderStatusExecutor(),
            new StockMovementExecutor(),
            new SetStockLevelExecutor(),
            new FinalizePurchaseLotExecutor(),
        ];
    }

    public function execute(array $identity, array $action): array
    {
        foreach ($this->executors as $executor) {
            if ($executor->supports($action)) {
                return $executor->execute($identity, $action);
            }
        }

        return [
            'executed' => false,
            'answer' => 'This pending action cannot be executed because no executor is available.',
            'summary' => [
                'executed' => false,
                'executor_implemented' => false,
            ],
            'items_preview' => [],
            'sources' => [
                [
                    'tool' => $action['tool'] ?? 'write_action',
                    'status' => 'executor_missing',
                ],
            ],
            'limitations' => [
                'No executor is available for this pending action.',
            ],
            'suggested_actions' => [
                'Cancel',
            ],
        ];
    }
}