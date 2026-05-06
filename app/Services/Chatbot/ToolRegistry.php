<?php

namespace App\Services\Chatbot;

use App\Services\Chatbot\Registry\OwnerInventoryToolRegistry;
use App\Services\Chatbot\Registry\OwnerProductToolRegistry;
use App\Services\Chatbot\Registry\OwnerStockProcurementToolRegistry;
use App\Services\Chatbot\Registry\PublicToolRegistry;
use App\Services\Chatbot\Registry\ToolGroupInterface;
use App\Services\Chatbot\Registry\RfqToolRegistry;
use App\Services\Chatbot\Registry\NotificationToolRegistry;
use App\Services\Chatbot\Registry\ProductIntelligenceToolRegistry;
use App\Services\Chatbot\Registry\StockIntelligenceExplanationToolRegistry;
use App\Services\Chatbot\Registry\OrderToolRegistry;
use App\Services\Chatbot\Registry\StockIntelligenceDashboardToolRegistry;
use App\Services\Chatbot\Registry\WriteActionToolRegistry;

class ToolRegistry
{
    private array $tools;

    /**
     * @var ToolGroupInterface[]
     */
    private array $groups;

    public function __construct()
    {
        $this->groups = [
            new WriteActionToolRegistry(),
            new PublicToolRegistry(),
            new OwnerInventoryToolRegistry(),
            new OwnerProductToolRegistry(),
            new OwnerStockProcurementToolRegistry(),
            new RfqToolRegistry(),
            new NotificationToolRegistry(),
            new ProductIntelligenceToolRegistry(),
            new StockIntelligenceExplanationToolRegistry(),
            new StockIntelligenceDashboardToolRegistry(),
            new OrderToolRegistry(),
        ];

        $this->tools = $this->loadTools();
    }

    public function get(string $intent): ?array
    {
        return $this->tools[$intent] ?? null;
    }

    public function all(): array
    {
        return $this->tools;
    }

    private function loadTools(): array
    {
        $tools = [];

        foreach ($this->groups as $group) {
            $tools = array_merge($tools, $group->tools());
        }

        return $tools;
    }
}