<?php

namespace App\Services\Chatbot;

use App\Services\Chatbot\Intent\CatalogIntentDetector;
use App\Services\Chatbot\Intent\CostIntentDetector;
use App\Services\Chatbot\Intent\DashboardIntentDetector;
use App\Services\Chatbot\Intent\HelpIntentDetector;
use App\Services\Chatbot\Intent\IntentDetectorInterface;
use App\Services\Chatbot\Intent\InventoryIntentDetector;
use App\Services\Chatbot\Intent\NavigationIntentDetector;
use App\Services\Chatbot\Intent\PromotionIntentDetector;
use App\Services\Chatbot\Intent\ReorderIntentDetector;
use App\Services\Chatbot\Intent\TextIntentUtils;
use App\Services\Chatbot\Intent\ShowMoreIntentDetector;
use App\Services\Chatbot\Intent\CatalogExtraIntentDetector;
use App\Services\Chatbot\Intent\OwnerProductIntentDetector;
use App\Services\Chatbot\Intent\OwnerStockProcurementIntentDetector;
use App\Services\Chatbot\Intent\ClarificationIntentDetector;
use App\Services\Chatbot\Intent\RfqIntentDetector;
use App\Services\Chatbot\Intent\NotificationIntentDetector;
use App\Services\Chatbot\Intent\ProductIntelligenceIntentDetector;
use App\Services\Chatbot\Intent\StockIntelligenceExplanationIntentDetector;
use App\Services\Chatbot\Intent\OrderIntentDetector;
use App\Services\Chatbot\Intent\StockIntelligenceDashboardIntentDetector;
use App\Services\Chatbot\Intent\ConfirmActionIntentDetector;
use App\Services\Chatbot\Intent\CancelActionIntentDetector;
use App\Services\Chatbot\WriteActions\Notification\NotificationWriteIntentDetector;
use App\Services\Chatbot\WriteActions\Rfq\RfqWriteIntentDetector;
use App\Services\Chatbot\WriteActions\Order\OrderWriteIntentDetector;
use App\Services\Chatbot\WriteActions\Stock\StockWriteIntentDetector;
use App\Services\Chatbot\WriteActions\PurchaseLot\PurchaseLotWriteIntentDetector;
use App\Services\Chatbot\Ai\AiIntentRouter;

class IntentClassifier
{
    /**
     * @var IntentDetectorInterface[]
     */
    private array $detectors;

    public function __construct()
    {
        $this->detectors = [
            new ConfirmActionIntentDetector(),
            new CancelActionIntentDetector(),
            new NotificationWriteIntentDetector(),

            new HelpIntentDetector(),
            new ClarificationIntentDetector(),

            new PurchaseLotWriteIntentDetector(),
            new StockWriteIntentDetector(),

            new RfqWriteIntentDetector(),
            new OrderWriteIntentDetector(),

            new NotificationIntentDetector(),

            new RfqIntentDetector(),
 
            new ProductIntelligenceIntentDetector(),
            new StockIntelligenceDashboardIntentDetector(),
            new StockIntelligenceExplanationIntentDetector(),
            
            new OrderIntentDetector(),

            new NavigationIntentDetector(),

            new NavigationIntentDetector(),
            new ShowMoreIntentDetector(),

            new InventoryIntentDetector(),
            new CostIntentDetector(),
            new ReorderIntentDetector(),
            new DashboardIntentDetector(),

            new OwnerProductIntentDetector(),
            new OwnerStockProcurementIntentDetector(),

            new CatalogExtraIntentDetector(),
            new PromotionIntentDetector(),
            new CatalogIntentDetector(),
        ];
    }

    public function classify(string $message): array
    {
        $text = strtolower(trim($message));

        $best = null;

        foreach ($this->detectors as $detector) {
            $result = $detector->detect($text);

            if ($result === null) {
                continue;
            }

            if ($this->isImmediateIntent($result)) {
                return $result;
            }

            $best = $this->betterResult($best, $result);
        }

        if (
            $best !== null
            && $this->isStrictEnough($best)
            && !$this->shouldHandoffToAi($message, $best)
        ) {
            return array_merge($best, [
                'source' => $best['source'] ?? 'deterministic_high_confidence',
                'deterministic_gate' => [
                    'accepted' => true,
                    'reason' => 'high_confidence_specific_intent',
                ],
            ]);
        }
        $aiRoute = (new AiIntentRouter())->route($message, $_SESSION['user'] ?? []);

        if (($aiRoute['matched'] ?? false) === true) {
            return [
                'intent' => $aiRoute['intent'],
                'confidence' => $aiRoute['confidence'] ?? 'medium',
                'params' => $aiRoute['params'] ?? [],
                'tools' => $aiRoute['tools'] ?? [],
                'operation_type' => $aiRoute['operation_type'] ?? null,
                'source' => 'ai_intent_router',
                'deterministic_candidate' => $best,
                'ai_router' => [
                    'used' => true,
                    'matched' => true,
                    'reason' => $aiRoute['reason'] ?? null,
                ],
            ];
        }

        return [
            'intent' => 'unknown',
            'confidence' => 'none',
            'params' => [],
            'operation_type' => 'unsupported',
            'source' => $best !== null
                ? 'deterministic_low_confidence_ai_failed'
                : 'no_deterministic_match_ai_failed',
            'deterministic_candidate' => $best,
            'deterministic_gate' => [
                'best_was_strict_enough' => $best !== null ? $this->isStrictEnough($best) : false,
                'handoff_to_ai' => $best !== null ? $this->shouldHandoffToAi($message, $best) : true,
            ],
            'ai_router' => [
                'used' => true,
                'matched' => false,
                'reason' => $aiRoute['reason'] ?? null,
                'router_intent' => $aiRoute['intent'] ?? null,
                'router_operation_type' => $aiRoute['operation_type'] ?? null,
                'router_confidence' => $aiRoute['confidence'] ?? null,
                'router_params' => $aiRoute['params'] ?? [],
                'router_error' => $aiRoute['error'] ?? null,
            ],
        ];
    }

    private function isImmediateIntent(array $result): bool
    {
        return in_array($result['intent'] ?? '', [
            'confirm_write_action',
            'cancel_write_action',
        ], true);
    }

    private function isStrictEnough(array $result): bool
    {
        return ($result['confidence'] ?? 'none') === 'high';
    }

    private function betterResult(?array $current, array $candidate): array
    {
        if ($current === null) {
            return $candidate;
        }

        $rank = [
            'none' => 0,
            'low' => 1,
            'medium' => 2,
            'high' => 3,
        ];

        $currentRank = $rank[$current['confidence'] ?? 'none'] ?? 0;
        $candidateRank = $rank[$candidate['confidence'] ?? 'none'] ?? 0;

        return $candidateRank > $currentRank ? $candidate : $current;
    }

    private function shouldHandoffToAi(string $message, array $candidate): bool
{
    if ($this->isImmediateIntent($candidate)) {
        return false;
    }

    $operationType = $candidate['operation_type'] ?? null;
    $intent = $candidate['intent'] ?? '';
    $text = strtolower(trim($message));

    /*
     * Write actions can stay deterministic if high confidence because they
     * still create preview only. They do not execute immediately.
     */
    if ($operationType === 'write_action') {
        return false;
    }

    /*
     * Strong exact read commands can remain deterministic.
     */
    if ($this->looksLikeExactSimpleCommand($text)) {
        return false;
    }

    /*
     * Generic read intents are allowed to exist, but should not steal broad,
     * analytical, multi-domain questions.
     */
        // Exact simple commands can stay deterministic.
    if ($this->looksLikeExactSimpleCommand($text)) {
        return false;
    }

    // Any read-only intent should be handed off if the request is clearly multi-domain.
    // Example: dependencies + cost + stock risk + procurement.
    if ($this->hasMixedDomainSignals($text) && $this->isLongOrComplexRequest($text)) {
        return true;
    }

    // Broad analytical language should also be handed off for known single-tool intents.
    $singleToolReadIntents = [
        'catalog_search',
        'product_details',
        'owner_product_details',
        'owner_product_dependencies',
        'cost_rollup',
        'component_stock_analysis',
        'purchase_lots_by_product',
        'order_summary',
        'rfq_summary',
        'inventory_summary',
        'dashboard_summary',
        'notifications_by_type',
    ];

    if (
        in_array($intent, $singleToolReadIntents, true)
        && $this->hasAnalyticalSignals($text)
        && $this->isLongOrComplexRequest($text)
    ) {
        return true;
    }

    return false;

    if ($this->isLongOrComplexRequest($text)) {
        return true;
    }

    if ($this->hasAnalyticalSignals($text)) {
        return true;
    }

    if ($this->hasMixedDomainSignals($text)) {
        return true;
    }

    return false;
}

    private function hasAnalyticalSignals(string $text): bool
    {
        $signals = [
            'why',
            'explain',
            'analyze',
            'analyse',
            'because',
            'reason',
            'reasons',
            'risk',
            'at risk',
            'need attention',
            'needs attention',
            'should i',
            'what should',
            'prioritize',
            'priority',
            'urgent',
            'recommend',
            'recommendation',
            'reorder',
            'forecast',
            'soon',
            'health',
            'situation',
            'problem',
            'issue',
            'issues',
        ];

        foreach ($signals as $signal) {
            if (str_contains($text, $signal)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeExactSimpleCommand(string $text): bool
    {
        $text = trim($text);

        $patterns = [
            '/^show\s+cost\s+rollup\s+for\s+product\s+\d+$/',
            '/^show\s+dependencies\s+for\s+product\s+\d+$/',
            '/^show\s+replacement\s+parts\s+for\s+product\s+\d+$/',
            '/^show\s+products$/',
            '/^list\s+products$/',
            '/^show\s+catalog(?:ue)?$/',
            '/^open\s+catalog(?:ue)?$/',
            '/^show\s+orders$/',
            '/^show\s+rfqs$/',
            '/^show\s+notifications$/',
            '/^show\s+inventory\s+alerts$/',
            '/^show\s+reorder\s+recommendations$/',
            '/^show\s+stock\s+intelligence$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

    private function isLongOrComplexRequest(string $text): bool
    {
        if (mb_strlen($text) > 75) {
            return true;
        }

        $separators = [
            ',',
            ':',
            ';',
            ' and ',
            ' with ',
            ' also ',
            ' plus ',
            ' as well as ',
        ];

        $hits = 0;

        foreach ($separators as $separator) {
            if (str_contains($text, $separator)) {
                $hits++;
            }
        }

        return $hits >= 2;
    }

    

    private function hasMixedDomainSignals(string $text): bool
    {
        $domains = 0;

        $domainSignals = [
            'catalog' => [
                'product',
                'products',
                'catalog',
                'catalogue',
                'component',
                'components',
            ],
            'stock' => [
                'stock',
                'inventory',
                'low stock',
                'out of stock',
                'threshold',
                'reorder',
                'risk',
            ],
            'procurement' => [
                'rfq',
                'supplier',
                'purchase',
                'purchase lot',
                'quote',
                'cost',
                'costs',
                'procurement',
            ],
            'orders' => [
                'order',
                'orders',
                'client',
                'delivered',
                'shipped',
                'cancelled',
                'processing',
            ],
            'intelligence' => [
                'risk',
                'forecast',
                'recommend',
                'attention',
                'urgent',
                'health',
                'explain',
                'analyze',
                'analyse',
            ],
        ];

        foreach ($domainSignals as $signals) {
            foreach ($signals as $signal) {
                if (str_contains($text, $signal)) {
                    $domains++;
                    break;
                }
            }
        }

        return $domains >= 2;
    }
}