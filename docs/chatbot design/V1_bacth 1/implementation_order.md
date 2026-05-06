# Rabbit Chatbot V1 Implementation Order

## Batch 1 — Core Foundation

Implement only:

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

This tests:

- role access
- permission denial
- exact tool selection
- public-safe tools
- owner-sensitive tools
- missing params
- navigation authorization
- normalized tool results
- answer composition
- security verification
- backend response convention

## Batch 2 — Catalogue Detail Expansion

```text
catalog_product_relations
catalog_product_promotions
catalog_product_reviews
catalog_product_rating_summary
```

## Batch 3 — Owner Internal Expansion

```text
owner_products_list
owner_product_details
product_dependencies_internal
purchase_lots_summary
purchase_lots_by_product
stock_item_status
stock_movements_by_product
rfq_summary
rfq_details
internal_promotions
internal_reviews
dashboard analytics extras
```

## Batch 4 — Requires Service Inspection

```text
user_notifications
user_unread_count
client_order_details
owner_orders_summary
product_recommendation
supplier_assigned_rfqs, if service exists
supplier_rfq_details, if service exists
supplier_my_quotes, if service exists
```

## Implementation Checklist

1. Add `ChatbotController.php`.
2. Add `app/Services/Chatbot/` service classes.
3. Implement `ToolRegistry`.
4. Implement `IntentClassifier` for Batch 1 intents.
5. Implement `OperationClassifier`.
6. Implement `PermissionGuard`.
7. Implement `NavigationResolver`.
8. Implement `ToolExecutor` for Batch 1 tools.
9. Implement `AnswerComposer`.
10. Implement `SecurityVerifier`.
11. Add route.
12. Test owner/client/supplier/guest behavior.
13. Only after Batch 1 passes, expand to Batch 2.
