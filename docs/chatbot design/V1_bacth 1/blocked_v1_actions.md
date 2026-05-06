# Blocked Chatbot V1 Actions

Chatbot V1 blocks all write actions, workflow actions, and destructive actions.

## Product Writes

```text
ProductService::store()
ProductService::update()
ProductService::delete()
```

## Dependency Writes

```text
DependencyService::addChild()
DependencyService::updateChild()
DependencyService::removeChild()
```

## Catalogue Review Creation

```text
CatalogReviewService::create()
```

Possible future flow:

```text
draft review → user confirms → create review
```

## Promotion Writes

```text
PromotionService::create()
PromotionService::update()
PromotionService::delete()
PromotionProductService::attach()
PromotionProductService::detach()
```

## Purchase Lot Writes

```text
PurchaseLotService::create()
PurchaseLotService::finalize()
```

`finalize()` is critical because it can trigger stock-in behavior.

## RFQ Writes / Workflow Actions

```text
RfqService::create()
RfqService::quote()
RfqService::accept()
RfqService::reject()
RfqService::expire()
RfqService::open()
```

Supplier quote is blocked in V1 even though it is supplier-facing.

Possible future flow:

```text
quote draft → supplier confirms → submit quote
```

## Stock Mutation

```text
StockService::recordMovement()
```

Note:

```text
recordMovement currently receives null as actor/user id.
```

Before chatbot write actions, audit identity must be fixed.

## Order Writes

```text
OrderService::create()
OrderService::updateStatus()
```

## Notification Writes

```text
NotificationService::markAsRead()
NotificationService::markAllAsRead()
```

These are low-risk but still blocked in V1.

## Review Moderation Writes

```text
ReviewService::updateStatus()
ReviewService::delete()
```

## Standard V1 Write Action Response

```json
{
  "message": "Chatbot action not supported yet.",
  "data": {
    "answer": "I can retrieve and explain information, but I cannot perform write actions yet.",
    "intent": "submit_quote",
    "confidence": "high",
    "role": "supplier",
    "operation_type": "write_action",
    "sources": [],
    "limitations": [
      "Write actions are disabled in Chatbot V1."
    ],
    "suggested_actions": [
      "Use the relevant form in the application."
    ]
  }
}
```
