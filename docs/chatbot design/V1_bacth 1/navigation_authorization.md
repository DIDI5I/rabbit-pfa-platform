# Navigation Authorization

Navigation is allowed in V1 only if it is role-authorized.

Navigation does not grant access.

The protection stack is:

```text
Chatbot Navigation PermissionGuard
        ↓
Frontend route guard
        ↓
Backend endpoint middleware
```

## Rule

```text
V1 allows read-only tools and role-authorized navigation only.
Navigation never bypasses route permissions.
```

## Owner Navigation Targets

```text
owner_dashboard          → /owner/dashboard
owner_inventory          → /owner/inventory
owner_stock_intelligence → /owner/stock-intelligence
owner_products           → /owner/products
owner_promotions         → /owner/promotions
owner_reviews            → /owner/reviews
owner_rfqs               → /owner/rfqs
owner_purchase_lots      → /owner/purchase-lots
owner_orders             → /owner/orders
```

Allowed role:

```text
owner
```

## Client Navigation Targets

```text
client_catalog        → /client/catalog
client_orders         → /client/orders
client_notifications  → /client/notifications
client_promotions     → /client/promotions
```

Allowed role:

```text
client
```

## Supplier Navigation Targets

```text
supplier_rfqs          → /supplier/rfqs
supplier_quotes        → /supplier/quotes
supplier_notifications → /supplier/notifications
```

Allowed role:

```text
supplier
```

Only include supplier pages if the frontend actually has them.

## Guest Navigation Targets

```text
login    → /login
register → /register
catalog  → /catalog
```

Allowed role:

```text
guest
```

## Navigation Response Example

```json
{
  "message": "Navigation target resolved.",
  "data": {
    "answer": "Opening the inventory page.",
    "intent": "navigate_inventory",
    "confidence": "high",
    "role": "owner",
    "operation_type": "navigation",
    "navigation": {
      "navigation_type": "internal_page",
      "target_page": "/owner/inventory",
      "previous_page_link": "/owner/dashboard",
      "bubble_text": "Back to previous page",
      "bubble_duration_ms": 4000
    },
    "sources": [
      {
        "tool": "navigate",
        "status": "used"
      }
    ],
    "limitations": [],
    "suggested_actions": []
  }
}
```

## Denied Navigation Example

```json
{
  "message": "Navigation not allowed.",
  "data": {
    "answer": "You do not have permission to open the owner dashboard.",
    "intent": "navigate_owner_dashboard",
    "confidence": "high",
    "role": "client",
    "operation_type": "navigation",
    "sources": [
      {
        "tool": "navigate",
        "status": "denied"
      }
    ],
    "limitations": [
      "This page requires owner access."
    ],
    "suggested_actions": [
      "Open catalogue",
      "Open my orders",
      "Open active promotions"
    ]
  }
}
```
