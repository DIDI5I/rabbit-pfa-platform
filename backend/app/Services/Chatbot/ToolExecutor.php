<?php

namespace App\Services\Chatbot;

use App\Services\Chatbot\Tools\ActivePromotionsTool;
use App\Services\Chatbot\Tools\CatalogProductDetailsTool;
use App\Services\Chatbot\Tools\CatalogProductPromotionsTool;
use App\Services\Chatbot\Tools\CatalogProductRatingSummaryTool;
use App\Services\Chatbot\Tools\CatalogProductRelationsTool;
use App\Services\Chatbot\Tools\CatalogProductReviewsTool;
use App\Services\Chatbot\Tools\CatalogSearchTool;
use App\Services\Chatbot\Tools\CostRollupTool;
use App\Services\Chatbot\Tools\DashboardStockSummaryTool;
use App\Services\Chatbot\Tools\InventoryAlertsTool;
use App\Services\Chatbot\Tools\InventorySummaryTool;
use App\Services\Chatbot\Tools\NavigationTool;
use App\Services\Chatbot\Tools\ReorderRecommendationsTool;
use App\Services\Chatbot\Tools\RoleHelpTool;
use App\Services\Chatbot\Tools\ShowMoreTool;
use App\Services\Chatbot\Tools\ToolHandlerInterface;
use App\Services\Chatbot\Tools\OwnerProductDetailsTool;
use App\Services\Chatbot\Tools\OwnerProductDependenciesTool;
use App\Services\Chatbot\Tools\PurchaseLotsByProductTool;
use App\Services\Chatbot\Tools\StockMovementsByComponentTool;
use App\Services\Chatbot\Tools\ClarificationTool;
use App\Services\Chatbot\Tools\Rfq\RfqSummaryTool;
use App\Services\Chatbot\Tools\Rfq\RfqDetailsTool;
use App\Services\Chatbot\Tools\Rfq\RfqsByStatusTool;
use App\Services\Chatbot\Tools\Rfq\RfqAllowedActionsTool;
use App\Services\Chatbot\Tools\Notification\NotificationSummaryTool;
use App\Services\Chatbot\Tools\Notification\UnreadNotificationsTool;
use App\Services\Chatbot\Tools\Notification\NotificationsByTypeTool;
use App\Services\Chatbot\Tools\ProductIntelligence\ProductIntelligenceSnapshotTool;
use App\Services\Chatbot\Tools\StockIntelligence\StockIntelligenceExplanationTool;
use App\Services\Chatbot\Tools\Order\OrderSummaryTool;
use App\Services\Chatbot\Tools\Order\RecentOrdersTool;
use App\Services\Chatbot\Tools\Order\OrdersByStatusTool;
use App\Services\Chatbot\Tools\Order\OrderDetailsTool;
use App\Services\Chatbot\Tools\StockIntelligenceDashboard\StockIntelligenceSummaryTool;
use App\Services\Chatbot\Tools\StockIntelligenceDashboard\StockIntelligenceDashboardExplanationTool;
use App\Services\Chatbot\WriteActions\Notification\MarkAllNotificationsReadPreviewTool;
use App\Services\Chatbot\WriteActions\Notification\MarkNotificationReadPreviewTool;
use App\Services\Chatbot\WriteActions\Rfq\RejectRfqPreviewTool;
use App\Services\Chatbot\WriteActions\Rfq\AcceptRfqPreviewTool;
use App\Services\Chatbot\WriteActions\Rfq\ExpireRfqPreviewTool;
use App\Services\Chatbot\WriteActions\Rfq\OpenRfqPreviewTool;
use App\Services\Chatbot\WriteActions\Order\UpdateOrderStatusPreviewTool;
use App\Services\Chatbot\WriteActions\Stock\StockMovementPreviewTool;
use App\Services\Chatbot\WriteActions\Stock\SetStockLevelPreviewTool;
use App\Services\Chatbot\WriteActions\PurchaseLot\FinalizePurchaseLotPreviewTool;
use App\Services\Chatbot\WriteActions\PurchaseLot\UpdatePendingPurchaseLotCostsTool;
use Throwable;


class ToolExecutor
{
    /**
     * @var ToolHandlerInterface[]
     */
    private array $handlers;

    public function __construct()
    {
        $this->handlers = [
            new RoleHelpTool(),
            new ClarificationTool(),
            new NavigationTool(),
            new ShowMoreTool(),

            new StockMovementPreviewTool(),
            new SetStockLevelPreviewTool(),

            new UpdatePendingPurchaseLotCostsTool(),
            new FinalizePurchaseLotPreviewTool(),

            new OpenRfqPreviewTool(),
            new ExpireRfqPreviewTool(),
            new RejectRfqPreviewTool(),
            new AcceptRfqPreviewTool(),

            new RfqSummaryTool(),
            new RfqDetailsTool(),
            new RfqsByStatusTool(),
            new RfqAllowedActionsTool(),

            new UpdateOrderStatusPreviewTool(),

            new MarkAllNotificationsReadPreviewTool(),
            new MarkNotificationReadPreviewTool(),

            new NotificationSummaryTool(),
            new UnreadNotificationsTool(),
            new NotificationsByTypeTool(),

            new ProductIntelligenceSnapshotTool(),
            new StockIntelligenceExplanationTool(),
            new StockIntelligenceSummaryTool(),
            new StockIntelligenceDashboardExplanationTool(),

            new OrderSummaryTool(),
            new RecentOrdersTool(),
            new OrdersByStatusTool(),
            new OrderDetailsTool(),

            new CatalogSearchTool(),
            new CatalogProductDetailsTool(),
            new CatalogProductRelationsTool(),
            new CatalogProductPromotionsTool(),
            new CatalogProductReviewsTool(),
            new CatalogProductRatingSummaryTool(),
            new ActivePromotionsTool(),

            new InventorySummaryTool(),
            new InventoryAlertsTool(),
            new CostRollupTool(),
            new ReorderRecommendationsTool(),
            new DashboardStockSummaryTool(),

            new OwnerProductDetailsTool(),
            new OwnerProductDependenciesTool(),

            new PurchaseLotsByProductTool(),
            new StockMovementsByComponentTool(),
        ];
       
    }

    public function execute(string $intent, array $params, array $identity, array $context = []): array
    {
        $role = $identity['role'] ?? 'guest';

        foreach ($this->handlers as $handler) {
            if (!$handler->supports($intent)) {
                continue;
            }

            try {
                return $handler->execute($intent, $params, $identity, $context);
            } catch (Throwable $e) {
                return [
                    'tool' => $intent,
                    'status' => 'failed',
                    'data' => [],
                    'meta' => [
                        'intent' => $intent,
                        'operation_type' => $intent === 'navigate' ? 'navigation' : 'read_only',
                        'role_scope' => $role,
                        'sensitive' => true,
                        'count' => null,
                    ],
                    'errors' => [
                        [
                            'code' => 'tool_execution_failed',
                            'message' => $e->getMessage(),
                        ],
                    ],
                ];
            }
        }

        return [
            'tool' => $intent,
            'status' => 'unsupported',
            'data' => [],
            'meta' => [
                'intent' => $intent,
                'operation_type' => 'unsupported',
                'role_scope' => $role,
                'sensitive' => false,
                'count' => null,
            ],
            'errors' => [
                [
                    'code' => 'unsupported_tool',
                    'message' => 'This tool is not supported.',
                ],
            ],
        ];
    }
}