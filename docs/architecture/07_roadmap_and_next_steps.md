# Rabbit — Roadmap and Next Steps

## Current Completed Work

```text
backend layered architecture
auth basics
product refactor
RFQ foundations
product relationship refactor
realistic MRO seed data
MAD currency decision
stock movement schema decision
stock movement files started
```

---

## Immediate Next Steps

### 1. Finish stock movement tests

Test:

```text
POST /stock/movements
GET /stock/{componentId}
GET /stock/{componentId}/movements
```

Confirm:

```text
IN increases stock
OUT decreases stock
OUT cannot go negative
invalid reason/type combinations are rejected
```

---

### 2. Migrate initial stock

```sql
INSERT INTO stock_movements (
    component_id,
    type,
    quantity,
    reason,
    reference_type,
    reference_id,
    notes,
    created_by
)
SELECT
    c.id,
    'in',
    c.stock_qty,
    'INITIAL_STOCK',
    'opening_stock',
    NULL,
    'Initial stock migrated from components.stock_qty',
    NULL
FROM components c
WHERE c.stock_qty > 0
AND NOT EXISTS (
    SELECT 1
    FROM stock_movements sm
    WHERE sm.component_id = c.id
    AND sm.reason = 'INITIAL_STOCK'
    AND sm.reference_type = 'opening_stock'
);
```

---

### 3. Implement inventory summary

Files:

```text
InventoryQuery
InventoryRepository
InventoryService
InventoryController
```

Endpoints:

```text
GET /inventory
GET /inventory/alerts
```

Inventory v1 fields:

```text
id
name
sku
category
legacy_stock_qty
current_stock
low_stock_threshold
stock_status
preferred_supplier
preferred_unit_cost_mad
lead_time_days
estimated_stock_value
```

---

### 4. Link RFQ to stock IN

Simplified demo behavior:

```text
accept RFQ → create stock movement IN
```

Better future behavior:

```text
RFQ accepted = commercial decision
purchase received = physical stock receipt
```

---

### 5. Link orders to stock OUT

When order is confirmed:

```text
create OUT movement
reason = SALE
reference_type = order
reference_id = order_id
```

Selling product affects only that exact product.

Relationships do not affect stock.

---

## Postponed Work

```text
ABC classification
stock minimum / maximum / safety / alert advanced formulas
CMUP
purchase_lots
selling price calculation
forecasting
advanced inventory KPIs
AI assistant
custom kits
```

---

## AI Layer Updated Direction

AI should become:

```text
controlled assistant over procurement + inventory services
```

Not:

```text
generic chatbot over database
```

First AI intents later:

```text
low_stock_items
stock_history
product_relationships
rfqs_needing_action
supplier_lookup
```

Important AI rule:

```text
AI must distinguish product relationships from physical stock movements.
```

---

## Final Project Core Loop

```text
Product
→ Supplier Source
→ RFQ
→ Quote
→ Accept
→ Stock IN
→ Order
→ Stock OUT
→ Inventory Summary
→ Low-stock Alert
→ RFQ Recommendation
```
