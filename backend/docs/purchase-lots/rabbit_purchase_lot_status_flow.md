# Rabbit — Purchase Lot Status Flow Documentation

## 1. Goal

We updated the purchase lot flow so stock movements depend on finalized purchase lots.

The new rule is:

```text
RFQ accepted
→ purchase_lot created with status = draft
→ no stock movement yet

Purchase lot finalized
→ PURCHASE_RECEIVED stock movement created
→ inventory increases
```

This keeps Rabbit compatible with the idea of **stock fictif**, while still requiring cost details to be completed before stock is counted.

---

## 2. Purchase Lot Meaning

A purchase lot represents a supplier purchase record.

It answers:

```text
What product was purchased?
From which supplier?
What quantity?
At what supplier unit price?
What extra acquisition costs were added?
What is the real total purchase cost?
What is the real unit purchase cost?
```

Purchase lot is the procurement/cost record.

Stock movement is the inventory record.

---

## 3. Purchase Lot Statuses

We added a `status` column to `purchase_lots`.

Statuses:

```text
draft
finalized
cancelled
```

Meaning:

```text
draft
```

The purchase lot exists, but cost details may still be incomplete.  
No stock movement should be created yet.

```text
finalized
```

The purchase lot costs are confirmed.  
Rabbit creates a `PURCHASE_RECEIVED` stock IN movement.

```text
cancelled
```

The purchase lot should not be used for stock or cost intelligence.

---

## 4. Database Change

SQL used:

```sql
ALTER TABLE purchase_lots
ADD COLUMN status ENUM('draft', 'finalized', 'cancelled') NOT NULL DEFAULT 'draft'
AFTER unit_purchase_cost;
```

For old valid purchase lots, we can mark them finalized:

```sql
UPDATE purchase_lots
SET status = 'finalized'
WHERE id > 0;
```

---

## 5. Purchase Lot Creation Flow

Endpoint:

```http
POST /purchase-lots
```

Creates a purchase lot with:

```text
status = draft
```

It calculates:

```text
supplier_total_price = quantity_received × supplier_unit_price

total_purchase_cost =
supplier_total_price
+ transport_cost
+ customs_cost
+ handling_cost
+ packaging_cost
+ order_preparation_cost
+ other_cost

unit_purchase_cost = total_purchase_cost / quantity_received
```

Important:

```text
Creating a draft purchase lot does NOT create stock movement.
Inventory does NOT increase yet.
```

---

## 6. Purchase Lot Finalization Flow

Endpoint:

```http
PATCH /purchase-lots/{id}/finalize
```

The owner/admin sends final cost details:

```json
{
  "transport_cost": 80,
  "customs_cost": 20,
  "handling_cost": 0,
  "packaging_cost": 0,
  "order_preparation_cost": 10,
  "other_cost": 0
}
```

Rabbit then:

```text
1. Loads the purchase lot.
2. Verifies it is not cancelled.
3. Verifies it is not already finalized.
4. Recalculates total_purchase_cost.
5. Recalculates unit_purchase_cost.
6. Sets status = finalized.
7. Creates a PURCHASE_RECEIVED stock IN movement.
8. Inventory increases.
```

---

## 7. Stock Movement Created on Finalization

When finalized, Rabbit creates:

```text
type = in
reason = PURCHASE_RECEIVED
reference_type = purchase_lot
reference_id = purchase_lot id
quantity = purchase_lot.quantity_received
```

Example:

```json
{
  "component_id": 11,
  "type": "in",
  "quantity": 10,
  "reason": "PURCHASE_RECEIVED",
  "reference_type": "purchase_lot",
  "reference_id": 5,
  "notes": "Stock IN generated after finalizing purchase lot #5"
}
```

---

## 8. Duplicate Protection

If a purchase lot is already finalized, finalizing it again returns a validation error.

Expected error:

```json
{
  "error": "Validation failed",
  "fields": {
    "status": [
      "Purchase lot is already finalized."
    ]
  }
}
```

Also, Rabbit checks whether a `PURCHASE_RECEIVED` stock movement already exists for that purchase lot before creating one.

---

## 9. RFQ Compromise Flow

For the project context, stock is treated as fictive.

Final compromise:

```text
RFQ accepted
→ purchase_lot created with status = draft
→ stock does not increase yet

Owner finalizes purchase lot
→ costs are completed
→ stock movement PURCHASE_RECEIVED is created
→ inventory increases
```

This avoids modeling a real warehouse receiving process, while still keeping cost calculation and stock management logically clean.

---

## 10. Completed Behavior

Confirmed working:

```text
POST /purchase-lots
→ creates draft purchase lot
→ does not increase stock

PATCH /purchase-lots/{id}/finalize
→ updates costs
→ marks purchase lot finalized
→ creates PURCHASE_RECEIVED stock movement
→ increases current stock

Repeated finalize
→ blocked by validation
```

---

## 11. Current Result

Rabbit now separates:

```text
Purchase lot draft = supplier purchase/cost record not finalized
Purchase lot finalized = confirmed cost record
Stock movement = inventory update after finalization
```

This gives Rabbit a clean compromise between:

```text
stock fictif
```

and

```text
real stock-management logic
```
