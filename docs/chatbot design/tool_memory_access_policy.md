# Tool, Memory, and Access Policy

## Tool Policy

Tools are backend-approved capabilities.

The chatbot must never call arbitrary SQL or arbitrary internal methods.

Every tool must define:

- intent
- tool name
- allowed roles
- required params
- sensitivity
- operation type
- read/write mode
- confirmation requirement
- record-level scope rules

Example registry item:

```php
'inventory_alerts' => [
    'roles' => ['owner'],
    'tool' => 'inventory_alerts',
    'operation_type' => 'read_only',
    'sensitive' => true,
    'required_params' => [],
    'requires_confirmation' => false,
],
```

## Tool Selection Rule

Select only the exact required tool.

Never unlock all tools.

```text
unknown intent = unlock nothing
```

## Memory Policy

Memory must be role-safe.

For V1, memory should be:

- session-only
- short-term
- minimal
- non-sensitive where possible
- role-scoped

## Owner Memory

Allowed short-term keys:

- last_product_id
- last_supplier_id
- last_rfq_id
- last_inventory_filter
- last_dashboard_metric
- last_reorder_query

## Client Memory

Allowed short-term keys:

- last_product_id
- last_order_id
- last_catalog_filter
- last_review_product_id

Forbidden for client memory:

- supplier_id
- purchase_cost
- stock_threshold
- reorder_recommendation
- stock_intelligence
- owner_dashboard_metric

## Supplier Memory

Allowed short-term keys:

- last_rfq_id
- last_quote_id
- last_requested_product_id

Forbidden for supplier memory:

- other_supplier_id
- owner_dashboard_metric
- client_order_id
- stock_intelligence
- cost_rollup
- other_supplier_quote

## Missing Parameter Resolution

If an intent requires a missing parameter:

1. Try role-safe memory.
2. If memory resolves safely, continue.
3. If not, return a needs-more-information response.
4. Do not guess.

Example:

```json
{
  "message": "Chatbot needs more information.",
  "data": {
    "answer": "Please specify the product you want the cost rollup for.",
    "intent": "cost_rollup",
    "confidence": "high",
    "role": "owner",
    "operation_type": "read_only",
    "sources": [],
    "limitations": [
      "Missing required parameter: product_id."
    ],
    "suggested_actions": [
      "Ask: cost rollup for product 1"
    ]
  }
}
```

## Access Verification Layers

Access is checked in layers:

1. Route access
2. Role-level tool access
3. Record-level scope access
4. Sensitive field filtering
5. Final output security verification

Role alone is not enough.

Record-level scope is mandatory for clients and suppliers.
