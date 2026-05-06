# Next Stage Plan — Chatbot / Backend Intelligence V1

## Current Position

The backend has completed route hardening and verification.

The next stage is not to add a free-form AI directly to the database.

The next stage is to build a controlled backend intelligence layer.

## Core Rule

The chatbot must not be the source of truth.

The backend is the source of truth.

The chatbot should only answer using approved backend endpoint results.

## Recommended Flow

```text
User question
   ↓
Intent detection
   ↓
Permission check
   ↓
Approved backend endpoint/tool call
   ↓
Grounded answer generation
   ↓
Standard Rabbit API response
```

## V1 Should Be Deterministic

Do not start with an LLM planner.

Start with a deterministic assistant:

```text
keyword/rule-based intent classifier
endpoint permission registry
tool executor
answer composer
```

This is safer and easier to debug.

## Minimum New Endpoint

```http
POST /chatbot/ask
```

Example request:

```json
{
  "message": "Which products should I reorder?"
}
```

Example response:

```json
{
  "message": "Chatbot answer generated successfully.",
  "data": {
    "answer": "Rabbit recommends reordering 3 products. Two are critical and one is high priority.",
    "intent": "stock_reorder_recommendation",
    "sources": [
      {
        "endpoint": "/stock/intelligence/reorder-recommendations",
        "status": "used"
      }
    ],
    "confidence": "high",
    "limitations": []
  }
}
```

## Minimum Files

Recommended structure:

```text
app/
  Controllers/
    ChatbotController.php

  Services/
    Chatbot/
      ChatbotService.php
      IntentClassifier.php
      EndpointRegistry.php
      ChatbotPermissionGuard.php
      ToolExecutor.php
      AnswerComposer.php
```

If the current project structure is flatter, use:

```text
controllers/ChatbotController.php
services/ChatbotService.php
services/ChatbotIntentService.php
services/ChatbotToolRegistry.php
services/ChatbotPermissionService.php
services/ChatbotAnswerService.php
```

## V1 Supported Questions

Start with:

```text
What products should I reorder?
Which items are out of stock?
Which items are low stock?
Show inventory alerts.
Show catalogue products.
What promotions are active?
What are the reviews for product X?
What is the rating for product X?
What products are compatible with product X?
Show dashboard stock summary.
```

## Required Safety Rules

The chatbot must:

```text
respect role permissions
never bypass middleware
never query the database directly
never invent stock or supplier values
never expose owner data to clients
return insufficient data when no endpoint exists
include source endpoint metadata in responses
```

## Owner-Only Chatbot Data

Owner can access:

```text
inventory
inventory alerts
stock intelligence
reorder recommendations
dashboard stock analytics
purchase lots
cost rollup
internal product data
internal promotions
internal reviews/moderation
```

## Client-Safe Chatbot Data

Client/public can access:

```text
catalog products
catalog product details
catalog product relations
active catalogue promotions
catalog product reviews
rating summaries
```

## Forbidden Client Data

Client chatbot must not expose:

```text
purchase costs
supplier-sensitive data
preferred supplier internals
inventory levels if not publicly exposed
stock intelligence
reorder recommendations
ABC/Gini analytics
owner dashboards
purchase lots
cost rollup
internal review moderation
```

## First Implementation Step

Create the route-permission registry first.

Then implement deterministic intent handling around that registry.
