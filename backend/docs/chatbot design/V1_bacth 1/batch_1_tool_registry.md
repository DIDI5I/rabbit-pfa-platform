# Batch 1 Tool Registry

Batch 1 is the first implementation set.

## Batch 1 Tools

```text
role_help
navigate
catalog_search
catalog_product_details
active_promotions
inventory_summary
inventory_alerts
cost_rollup
reorder_recommendations
dashboard_stock_summary
```

## Batch 1 Registry Draft

```php
return [
    'role_help' => [
        'roles' => ['guest', 'client', 'supplier', 'owner'],
        'tool' => 'role_help',
        'operation_type' => 'read_only',
        'sensitive' => false,
        'required_params' => [],
        'requires_confirmation' => false,
    ],

    'navigate' => [
        'roles' => ['guest', 'client', 'supplier', 'owner'],
        'tool' => 'navigate',
        'operation_type' => 'navigation',
        'sensitive' => false,
        'required_params' => ['target'],
        'requires_confirmation' => false,
        'requires_role_authorization' => true,
    ],

    'catalog_search' => [
        'roles' => ['guest', 'client', 'supplier', 'owner'],
        'tool' => 'catalog_search',
        'execution' => [CatalogProductService::class, 'list'],
        'operation_type' => 'read_only',
        'sensitive' => false,
        'required_params' => [],
        'requires_confirmation' => false,
    ],

    'catalog_product_details' => [
        'roles' => ['guest', 'client', 'supplier', 'owner'],
        'tool' => 'catalog_product_details',
        'execution' => [CatalogProductService::class, 'findById'],
        'operation_type' => 'read_only',
        'sensitive' => false,
        'required_params' => ['product_id'],
        'requires_confirmation' => false,
    ],

    'active_promotions' => [
        'roles' => ['guest', 'client', 'owner'],
        'tool' => 'active_promotions',
        'execution' => [CatalogPromotionService::class, 'activeList'],
        'operation_type' => 'read_only',
        'sensitive' => false,
        'required_params' => [],
        'requires_confirmation' => false,
    ],

    'inventory_summary' => [
        'roles' => ['owner'],
        'tool' => 'inventory_summary',
        'execution' => [InventoryService::class, 'all'],
        'operation_type' => 'read_only',
        'sensitive' => true,
        'required_params' => [],
        'requires_confirmation' => false,
    ],

    'inventory_alerts' => [
        'roles' => ['owner'],
        'tool' => 'inventory_alerts',
        'execution' => [InventoryService::class, 'alerts'],
        'operation_type' => 'read_only',
        'sensitive' => true,
        'required_params' => [],
        'requires_confirmation' => false,
    ],

    'cost_rollup' => [
        'roles' => ['owner'],
        'tool' => 'cost_rollup',
        'execution' => [CostRollupService::class, 'calculate'],
        'operation_type' => 'read_only',
        'sensitive' => true,
        'required_params' => ['product_id'],
        'requires_confirmation' => false,
    ],

    'reorder_recommendations' => [
        'roles' => ['owner'],
        'tool' => 'reorder_recommendations',
        'execution' => [StockIntelligenceService::class, 'reorderRecommendations'],
        'operation_type' => 'read_only',
        'sensitive' => true,
        'required_params' => [],
        'optional_params' => [
            'period_days',
            'forecast_days',
            'only_recommended',
            'priority',
            'confidence',
            'model',
            'include_diagnostics',
        ],
        'requires_confirmation' => false,
    ],

    'dashboard_stock_summary' => [
        'roles' => ['owner'],
        'tool' => 'dashboard_stock_summary',
        'execution' => [DashboardStockService::class, 'general'],
        'operation_type' => 'read_only',
        'sensitive' => true,
        'required_params' => [],
        'requires_confirmation' => false,
    ],
];
```

## Role Classification

### Public-safe

```text
role_help
navigate, if target allowed
catalog_search
catalog_product_details
active_promotions
```

### Owner-sensitive

```text
inventory_summary
inventory_alerts
cost_rollup
reorder_recommendations
dashboard_stock_summary
```
