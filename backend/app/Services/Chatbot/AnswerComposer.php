<?php

namespace App\Services\Chatbot;

use App\Services\Chatbot\Presenters\ActivePromotionsPresenter;
use App\Services\Chatbot\Presenters\CatalogProductDetailsPresenter;
use App\Services\Chatbot\Presenters\CatalogProductPromotionsPresenter;
use App\Services\Chatbot\Presenters\CatalogProductRatingSummaryPresenter;
use App\Services\Chatbot\Presenters\CatalogProductRelationsPresenter;
use App\Services\Chatbot\Presenters\CatalogProductReviewsPresenter;
use App\Services\Chatbot\Presenters\CatalogSearchPresenter;
use App\Services\Chatbot\Presenters\CostRollupPresenter;
use App\Services\Chatbot\Presenters\DashboardStockSummaryPresenter;
use App\Services\Chatbot\Presenters\InventoryAlertsPresenter;
use App\Services\Chatbot\Presenters\InventorySummaryPresenter;
use App\Services\Chatbot\Presenters\NavigationPresenter;
use App\Services\Chatbot\Presenters\ReorderRecommendationsPresenter;
use App\Services\Chatbot\Presenters\ResponsePresenterInterface;
use App\Services\Chatbot\Presenters\RoleHelpPresenter;
use App\Services\Chatbot\Presenters\ShowMorePresenter;
use App\Services\Chatbot\Presenters\OwnerProductDetailsPresenter;
use App\Services\Chatbot\Presenters\OwnerProductDependenciesPresenter;
use App\Services\Chatbot\Presenters\PurchaseLotsByProductPresenter;
use App\Services\Chatbot\Presenters\StockMovementsByComponentPresenter;
use App\Services\Chatbot\Presenters\ClarificationPresenter;
use App\Services\Chatbot\Presenters\Rfq\RfqSummaryPresenter;
use App\Services\Chatbot\Presenters\Rfq\RfqDetailsPresenter;
use App\Services\Chatbot\Presenters\Rfq\RfqsByStatusPresenter;
use App\Services\Chatbot\Presenters\Rfq\RfqAllowedActionsPresenter;
use App\Services\Chatbot\Presenters\Notification\NotificationSummaryPresenter;
use App\Services\Chatbot\Presenters\Notification\UnreadNotificationsPresenter;
use App\Services\Chatbot\Presenters\Notification\NotificationsByTypePresenter;
use App\Services\Chatbot\Presenters\ProductIntelligence\ProductIntelligenceSnapshotPresenter;
use App\Services\Chatbot\Presenters\StockIntelligence\StockIntelligenceExplanationPresenter;
use App\Services\Chatbot\Presenters\Order\OrderSummaryPresenter;
use App\Services\Chatbot\Presenters\Order\RecentOrdersPresenter;
use App\Services\Chatbot\Presenters\Order\OrdersByStatusPresenter;
use App\Services\Chatbot\Presenters\Order\OrderDetailsPresenter;
use App\Services\Chatbot\Presenters\StockIntelligenceDashboard\StockIntelligenceSummaryPresenter;
use App\Services\Chatbot\Presenters\StockIntelligenceDashboard\StockIntelligenceDashboardExplanationPresenter;
use App\Services\Chatbot\Presenters\ComponentStockAnalysisPresenter;
use App\Services\Chatbot\Ai\AiAnswerRefiner;

class AnswerComposer
{
    /**
     * @var ResponsePresenterInterface[]
     */
    private array $presenters;

    public function __construct()
    {
        $this->presenters = [
            new RoleHelpPresenter(),
            new ClarificationPresenter(),
            new NavigationPresenter(),
            new ShowMorePresenter(),

            new RfqSummaryPresenter(),
            new RfqDetailsPresenter(),
            new RfqsByStatusPresenter(),
            new RfqAllowedActionsPresenter(),

            new ComponentStockAnalysisPresenter(),

            new NotificationSummaryPresenter(),
            new UnreadNotificationsPresenter(),
            new NotificationsByTypePresenter(),

            new ProductIntelligenceSnapshotPresenter(),
            new StockIntelligenceExplanationPresenter(),
            new StockIntelligenceSummaryPresenter(),
            new StockIntelligenceDashboardExplanationPresenter(),

            new OrderSummaryPresenter(),
            new RecentOrdersPresenter(),
            new OrdersByStatusPresenter(),
            new OrderDetailsPresenter(),

            new CatalogSearchPresenter(),
            new CatalogProductDetailsPresenter(),
            new CatalogProductRelationsPresenter(),
            new CatalogProductPromotionsPresenter(),
            new CatalogProductReviewsPresenter(),
            new CatalogProductRatingSummaryPresenter(),

            new ActivePromotionsPresenter(),

            new InventorySummaryPresenter(),
            new InventoryAlertsPresenter(),
            new CostRollupPresenter(),
            new ReorderRecommendationsPresenter(),
            new DashboardStockSummaryPresenter(),

            new OwnerProductDetailsPresenter(),
            new OwnerProductDependenciesPresenter(),
            new PurchaseLotsByProductPresenter(),
            new StockMovementsByComponentPresenter(),
        ];

    }

    public function compose(array $toolResult, array $identity): array
    {
        $role = $identity['role'] ?? 'guest';
        $status = $toolResult['status'] ?? 'failed';

        if ($status === 'permission_denied') {
            return $this->permissionDenied($toolResult, $role);
        }

        if ($status === 'missing_params') {
            return $this->missingParams($toolResult, $role);
        }

        if ($status === 'not_found') {
            return $this->notFound($toolResult, $role);
        }

        if ($status === 'needs_clarification') {
            return $this->needsClarification($toolResult, $role);
        }

        if ($status === 'failed' || $status === 'unsupported') {
            return $this->failed($toolResult, $role);
        }

        $tool = $toolResult['tool']
            ?? $toolResult['meta']['intent']
            ?? $toolResult['intent']
            ?? 'unknown';

        $toolResult['tool'] = $tool;

        foreach ($this->presenters as $presenter) {
            if ($presenter->supports($tool)) {
                return $presenter->present($toolResult, $identity);
            }
        }

        return $this->generic($toolResult, $role);
    }

    private function permissionDenied(array $toolResult, string $role): array
    {
        return [
            'message' => 'Chatbot could not answer this request.',
            'data' => [
                'answer' => 'You do not have permission to access this information.',
                'intent' => $toolResult['meta']['intent'] ?? $toolResult['tool'],
                'confidence' => 'high',
                'role' => $role,
                'operation_type' => $toolResult['meta']['operation_type'] ?? 'read_only',
                'summary' => [],
                'items_preview' => [],
                'result_meta' => [],
                'sources' => [
                    [
                        'tool' => $toolResult['tool'],
                        'status' => 'denied',
                    ],
                ],
                'limitations' => array_column($toolResult['errors'] ?? [], 'message'),
                'suggested_actions' => (new RoleProfileRegistry())->get($role)['suggested_actions'] ?? [],
            ],
        ];
    }

    private function missingParams(array $toolResult, string $role): array
    {
        $missing = $toolResult['data']['missing_params'] ?? [];

        $answer = in_array('product_id', $missing, true)
            ? 'Please specify the product ID.'
            : 'Please provide the missing required information.';

        return [
            'message' => 'Chatbot needs more information.',
            'data' => [
                'answer' => $answer,
                'intent' => $toolResult['meta']['intent'] ?? $toolResult['tool'],
                'confidence' => 'high',
                'role' => $role,
                'operation_type' => $toolResult['meta']['operation_type'] ?? 'read_only',
                'summary' => [],
                'items_preview' => [],
                'result_meta' => [],
                'sources' => [],
                'limitations' => array_column($toolResult['errors'] ?? [], 'message'),
                'suggested_actions' => [],
            ],
        ];
    }

    private function notFound(array $toolResult, string $role): array
    {
        return [
            'message' => 'Chatbot could not find the requested resource.',
            'data' => [
                'answer' => 'I could not find the requested resource.',
                'intent' => $toolResult['meta']['intent'] ?? $toolResult['tool'],
                'confidence' => 'high',
                'role' => $role,
                'operation_type' => $toolResult['meta']['operation_type'] ?? 'read_only',
                'summary' => [],
                'items_preview' => [],
                'result_meta' => [],
                'sources' => [
                    [
                        'tool' => $toolResult['tool'],
                        'status' => 'failed',
                    ],
                ],
                'limitations' => array_column($toolResult['errors'] ?? [], 'message'),
                'suggested_actions' => [],
            ],
        ];
    }

    private function failed(array $toolResult, string $role): array
    {
        return [
            'message' => 'Chatbot could not answer this request.',
            'data' => [
                'answer' => 'Rabbit does not currently expose enough backend data to answer this safely.',
                'intent' => $toolResult['meta']['intent'] ?? $toolResult['tool'],
                'confidence' => 'none',
                'role' => $role,
                'operation_type' => $toolResult['meta']['operation_type'] ?? 'read_only',
                'summary' => [],
                'items_preview' => [],
                'result_meta' => [],
                'sources' => [],
                'limitations' => array_column($toolResult['errors'] ?? [], 'message'),
                'suggested_actions' => [],
            ],
        ];
    }

    private function generic(array $toolResult, string $role): array
    {
        return [
            'message' => 'Chatbot answer generated successfully.',
            'data' => [
                'answer' => 'I found relevant information.',
                'intent' => $toolResult['meta']['intent'] ?? $toolResult['tool'],
                'confidence' => 'high',
                'role' => $role,
                'operation_type' => $toolResult['meta']['operation_type'] ?? 'read_only',
                'summary' => [],
                'items_preview' => [],
                'result_meta' => [],
                'sources' => [
                    [
                        'tool' => $toolResult['tool'],
                        'status' => 'used',
                    ],
                ],
                'limitations' => [],
                'suggested_actions' => [],
            ],
        ];
    }

    private function needsClarification(array $toolResult, string $role): array
    {   
        (new \App\Services\Chatbot\ClarificationManager())->remember($toolResult);

        $resolution = $toolResult['data']['resolution'] ?? [];
        $matches = $resolution['matches'] ?? [];

        return [
            'message' => 'Chatbot needs clarification.',
            'data' => [
                'answer' => 'I found multiple possible matches. Which one do you mean?',
                'intent' => $toolResult['meta']['intent'] ?? $toolResult['tool'],
                'confidence' => 'medium',
                'role' => $role,
                'operation_type' => $toolResult['meta']['operation_type'] ?? 'read_only',
                'summary' => [
                    'query' => $resolution['query'] ?? null,
                    'match_count' => count($matches),
                ],
                'items_preview' => $matches,
                'result_meta' => [
                    'has_more' => false,
                    'total' => count($matches),
                    'shown' => count($matches),
                    'shown_this_response' => count($matches),
                ],
                'sources' => [
                    [
                        'tool' => $toolResult['tool'],
                        'status' => 'needs_clarification',
                    ],
                ],
                'limitations' => array_column($toolResult['errors'] ?? [], 'message'),
                'suggested_actions' => [
                    'Reply with the product ID',
                    'Reply with the exact SKU',
                ],
            ],
        ];
    }
}