# Implementation Next Steps

## Immediate Next Stage

Begin implementing Rabbit Chatbot V1 controlled backend flow.

Do not add LLM/API first.

Start with deterministic infrastructure.

## Step 1 — Add Route

```php
$router->postRoute('/chatbot/ask', [ChatbotController::class, 'ask'])
       ->only([AuthMiddleware::class]);
```

Guest handling can be added later through a public-safe guest endpoint or by allowing the controller to detect unauthenticated state if routing supports it.

## Step 2 — Create Core Files

Recommended files:

```text
app/Controllers/ChatbotController.php

app/Services/Chatbot/ChatbotService.php
app/Services/Chatbot/IdentityResolver.php
app/Services/Chatbot/RoleProfileRegistry.php
app/Services/Chatbot/MemoryPolicy.php
app/Services/Chatbot/MemoryManager.php
app/Services/Chatbot/IntentClassifier.php
app/Services/Chatbot/OperationClassifier.php
app/Services/Chatbot/ToolRegistry.php
app/Services/Chatbot/PermissionGuard.php
app/Services/Chatbot/ToolExecutor.php
app/Services/Chatbot/AnswerComposer.php
app/Services/Chatbot/SecurityVerifier.php
app/Services/Chatbot/ChatbotLogger.php
```

API fallback files later:

```text
app/Services/Chatbot/Ai/ApiIntentClassifier.php
app/Services/Chatbot/Ai/ApiAnswerComposer.php
app/Services/Chatbot/Ai/GroqProvider.php
app/Services/Chatbot/Ai/GeminiProvider.php
app/Services/Chatbot/Ai/OllamaProvider.php
```

## Step 3 — Implement V1 Intents

Start with:

```text
help
catalog_search
catalog_product_details
catalog_product_reviews
catalog_product_rating
active_promotions
product_relations
inventory_summary
inventory_alerts
reorder_recommendations
dashboard_stock_summary
cost_rollup
navigation
unknown
```

## Step 4 — Implement Role Gating

Owner-only:

```text
inventory_summary
inventory_alerts
reorder_recommendations
dashboard_stock_summary
cost_rollup
purchase_lots
rfqs
internal_reviews
internal_promotions
```

Client-safe:

```text
catalog_search
catalog_product_details
catalog_product_reviews
catalog_product_rating
active_promotions
product_relations
my_orders
my_notifications
```

Supplier-safe:

```text
supplier_assigned_rfqs
supplier_rfq_details
my_quotes
supplier_notifications
limited_product_details
```

## Step 5 — Implement Deterministic Classifier

Example rules:

```text
"low stock", "rupture", "stock alert" → inventory_alerts
"reorder", "restock", "réapprovisionnement" → reorder_recommendations
"promotion", "discount", "promo" → active_promotions
"review", "rating", "avis" → product_reviews / rating
"cost", "rollup", "coût" → cost_rollup
"dashboard" → dashboard_stock_summary
"open", "go to", "navigate" → navigation
```

## Step 6 — Implement Tool Execution

Use existing backend services/queries.

Do not duplicate business logic.

Examples:

```text
inventory_alerts → InventoryQuery::alerts / InventoryService
inventory_summary → InventoryQuery::all / InventoryService
reorder_recommendations → StockIntelligenceService
cost_rollup → CostRollupService
active_promotions → CatalogPromotionService
product_reviews → CatalogReviewService
```

## Step 7 — Implement Security Verifier

Verify:

- role-safe output
- no forbidden fields
- no hallucinated data
- answer grounded in tool result
- backend response convention followed

## Step 8 — Tests

Test owner:

```text
show low stock
what should I reorder
cost rollup for product 1
show dashboard
open inventory
```

Test client:

```text
show products
show promotions
reviews for product 1
what is low in stock
cost rollup for product 1
```

Test supplier:

```text
show my RFQs
show RFQ 1
show inventory alerts
show cost rollup
```

Expected:

- owner internal requests pass
- client internal requests denied
- supplier only sees supplier-scoped data
- unknown intent returns safe response
- all outputs follow message + data convention

## Step 9 — Add API Fallback Later

Only after deterministic V1 works.

Preferred broke-friendly fallback order:

```text
rules first
Groq free tier
Gemini free tier
Ollama local fallback
safe generic response
```
