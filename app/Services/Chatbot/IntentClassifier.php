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
            new NotificationIntentDetector(),

            new RfqWriteIntentDetector(),
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

        foreach ($this->detectors as $detector) {
            $result = $detector->detect($text);

            if ($result !== null) {
                return $result;
            }
        }

        return TextIntentUtils::result('unknown', [], 'none');
    }
}