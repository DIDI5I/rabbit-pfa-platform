# Batch 1 Normalized Return Types

## 1. role_help

```php
[
    'tool' => 'role_help',
    'status' => 'success',
    'data' => [
        'role' => 'owner',
        'available_topics' => [
            'inventory',
            'inventory alerts',
            'cost rollup',
            'reorder recommendations',
            'stock dashboard',
            'catalogue products',
            'active promotions',
        ],
        'restricted_topics' => [],
        'example_questions' => [
            'Show inventory alerts',
            'What should I reorder?',
            'Show cost rollup for product 1',
            'Open inventory page',
        ],
    ],
    'meta' => [
        'intent' => 'role_help',
        'operation_type' => 'read_only',
        'role_scope' => 'owner',
        'sensitive' => false,
        'count' => 7,
    ],
    'errors' => [],
]
```

---

## 2. navigate

### Success

```php
[
    'tool' => 'navigate',
    'status' => 'success',
    'data' => [
        'navigation' => [
            'navigation_type' => 'internal_page',
            'target_key' => 'owner_inventory',
            'target_page' => '/owner/inventory',
            'previous_page_link' => '/owner/dashboard',
            'bubble_text' => 'Back to previous page',
            'bubble_duration_ms' => 4000,
        ],
    ],
    'meta' => [
        'intent' => 'navigate_inventory',
        'operation_type' => 'navigation',
        'role_scope' => 'owner',
        'sensitive' => true,
        'count' => null,
    ],
    'errors' => [],
]
```

### Permission Denied

```php
[
    'tool' => 'navigate',
    'status' => 'permission_denied',
    'data' => [
        'requested_target' => 'owner_dashboard',
        'allowed_targets' => [
            'client_catalog',
            'client_orders',
            'client_notifications',
            'client_promotions',
        ],
    ],
    'meta' => [
        'intent' => 'navigate_owner_dashboard',
        'operation_type' => 'navigation',
        'role_scope' => 'client',
        'sensitive' => true,
        'count' => null,
    ],
    'errors' => [
        [
            'code' => 'navigation_forbidden',
            'message' => 'This navigation target requires owner access.',
        ],
    ],
]
```

---

## 3. catalog_search

```php
[
    'tool' => 'catalog_search',
    'status' => 'success',
    'data' => [
        'products' => [
            [
                'id' => 1,
                'name' => 'Pompe centrifuge horizontale XR200 7.5 kW',
                'sku' => 'ASM-PMP-XR200',
                'category' => 'assembly',
                'description' => null,
                'rating_average' => 4.5,
                'review_count' => 12,
                'has_active_promotion' => true,
            ],
        ],
        'count' => 1,
    ],
    'meta' => [
        'intent' => 'catalog_search',
        'operation_type' => 'read_only',
        'role_scope' => 'client',
        'sensitive' => false,
        'count' => 1,
    ],
    'errors' => [],
]
```

Forbidden fields for non-owner:

```text
current_stock
low_stock_threshold
purchase_cost
preferred_unit_cost_mad
supplier_internal_cost
reorder_status
stock_intelligence
purchase_lots
cost_rollup
```

---

## 4. catalog_product_details

```php
[
    'tool' => 'catalog_product_details',
    'status' => 'success',
    'data' => [
        'product' => [
            'id' => 1,
            'name' => 'Pompe centrifuge horizontale XR200 7.5 kW',
            'sku' => 'ASM-PMP-XR200',
            'category' => 'assembly',
            'description' => 'Public product description here.',
            'rating_average' => 4.5,
            'review_count' => 12,
            'has_active_promotion' => true,
        ],
    ],
    'meta' => [
        'intent' => 'catalog_product_details',
        'operation_type' => 'read_only',
        'role_scope' => 'client',
        'sensitive' => false,
        'count' => 1,
    ],
    'errors' => [],
]
```

### Missing Params

```php
[
    'tool' => 'catalog_product_details',
    'status' => 'missing_params',
    'data' => [
        'missing_params' => ['product_id'],
    ],
    'meta' => [
        'intent' => 'catalog_product_details',
        'operation_type' => 'read_only',
        'role_scope' => 'client',
        'sensitive' => false,
        'count' => null,
    ],
    'errors' => [
        [
            'code' => 'missing_product_id',
            'message' => 'product_id is required.',
        ],
    ],
]
```

---

## 5. active_promotions

```php
[
    'tool' => 'active_promotions',
    'status' => 'success',
    'data' => [
        'promotions' => [
            [
                'id' => 1,
                'title' => 'Spring Maintenance Offer',
                'description' => 'Public promotion description.',
                'starts_at' => '2026-05-01',
                'ends_at' => '2026-05-31',
                'products_count' => 5,
            ],
        ],
        'count' => 1,
    ],
    'meta' => [
        'intent' => 'active_promotions',
        'operation_type' => 'read_only',
        'role_scope' => 'client',
        'sensitive' => false,
        'count' => 1,
    ],
    'errors' => [],
]
```

---

## 6. inventory_summary

```php
[
    'tool' => 'inventory_summary',
    'status' => 'success',
    'data' => [
        'items' => [
            [
                'id' => 1,
                'name' => 'Pompe centrifuge horizontale XR200 7.5 kW',
                'sku' => 'ASM-PMP-XR200',
                'category' => 'assembly',
                'legacy_stock_qty' => 10,
                'low_stock_threshold' => 5,
                'current_stock' => 12,
                'stock_status' => 'OK',
                'preferred_supplier' => 'Supplier Name',
                'preferred_unit_cost_mad' => 157040.6,
                'lead_time_days' => 14,
                'estimated_stock_value_mad' => 1884487.2,
            ],
        ],
        'count' => 1,
        'summary' => [
            'total_items' => 1,
            'ok_count' => 1,
            'low_stock_count' => 0,
            'out_of_stock_count' => 0,
            'total_estimated_stock_value_mad' => 1884487.2,
        ],
    ],
    'meta' => [
        'intent' => 'inventory_summary',
        'operation_type' => 'read_only',
        'role_scope' => 'owner',
        'sensitive' => true,
        'count' => 1,
    ],
    'errors' => [],
]
```

---

## 7. inventory_alerts

```php
[
    'tool' => 'inventory_alerts',
    'status' => 'success',
    'data' => [
        'alerts' => [
            [
                'id' => 27,
                'name' => 'Filtre retour hydraulique 10 microns',
                'sku' => 'CMP-HYD-FLT10',
                'category' => 'component',
                'current_stock' => 0,
                'low_stock_threshold' => 10,
                'stock_status' => 'OUT_OF_STOCK',
                'preferred_supplier' => 'Casatech Hydraulique',
                'lead_time_days' => 6,
            ],
        ],
        'count' => 1,
        'summary' => [
            'total_alerts' => 1,
            'out_of_stock_count' => 1,
            'low_stock_count' => 0,
        ],
    ],
    'meta' => [
        'intent' => 'inventory_alerts',
        'operation_type' => 'read_only',
        'role_scope' => 'owner',
        'sensitive' => true,
        'count' => 1,
    ],
    'errors' => [],
]
```

---

## 8. cost_rollup

```php
[
    'tool' => 'cost_rollup',
    'status' => 'success',
    'data' => [
        'product_id' => 1,
        'total_material_cost_mad' => 157040.6,
        'unique_components' => 14,
    ],
    'meta' => [
        'intent' => 'cost_rollup',
        'operation_type' => 'read_only',
        'role_scope' => 'owner',
        'sensitive' => true,
        'count' => 1,
    ],
    'errors' => [],
]
```

### Missing Params

```php
[
    'tool' => 'cost_rollup',
    'status' => 'missing_params',
    'data' => [
        'missing_params' => ['product_id'],
    ],
    'meta' => [
        'intent' => 'cost_rollup',
        'operation_type' => 'read_only',
        'role_scope' => 'owner',
        'sensitive' => true,
        'count' => null,
    ],
    'errors' => [
        [
            'code' => 'missing_product_id',
            'message' => 'product_id is required for cost rollup.',
        ],
    ],
]
```

---

## 9. reorder_recommendations

```php
[
    'tool' => 'reorder_recommendations',
    'status' => 'success',
    'data' => [
        'recommendations' => [
            [
                'product_id' => 1,
                'product_name' => 'Pompe centrifuge horizontale XR200 7.5 kW',
                'sku' => 'ASM-PMP-XR200',
                'current_stock' => 7,
                'forecasted_demand' => 12,
                'recommended_quantity' => 5,
                'priority' => 'high',
                'confidence' => 'medium',
                'reason' => 'Projected demand exceeds available stock.',
            ],
        ],
        'count' => 1,
        'summary' => [
            'recommended_count' => 1,
            'critical_count' => 0,
            'high_priority_count' => 1,
            'medium_priority_count' => 0,
            'low_priority_count' => 0,
        ],
        'parameters' => [
            'period_days' => 90,
            'forecast_days' => 30,
            'filters' => [
                'only_recommended' => null,
                'priority' => null,
                'confidence' => null,
                'model' => null,
                'include_diagnostics' => null,
            ],
        ],
    ],
    'meta' => [
        'intent' => 'reorder_recommendations',
        'operation_type' => 'read_only',
        'role_scope' => 'owner',
        'sensitive' => true,
        'count' => 1,
    ],
    'errors' => [],
]
```

---

## 10. dashboard_stock_summary

```php
[
    'tool' => 'dashboard_stock_summary',
    'status' => 'success',
    'data' => [
        'summary' => [
            'total_products' => 34,
            'total_inventory_value_mad' => 2500000.0,
            'ok_count' => 29,
            'low_stock_count' => 3,
            'out_of_stock_count' => 2,
            'stock_health_score' => 85,
        ],
    ],
    'meta' => [
        'intent' => 'dashboard_stock_summary',
        'operation_type' => 'read_only',
        'role_scope' => 'owner',
        'sensitive' => true,
        'count' => null,
    ],
    'errors' => [],
]
```
