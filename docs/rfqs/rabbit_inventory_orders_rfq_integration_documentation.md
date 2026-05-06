# Rabbit — Inventory, Orders, and RFQ Stock Integration Documentation

## 1. Current Stage Completed

Rabbit now has a working stock-driven backend loop:

```text
Stock movement core        ✅ done
Inventory V1               ✅ done
Orders foundation           ✅ done
Order → stock OUT           ✅ done
Order cancellation restore  ✅ done
RFQ accepted → stock IN     ✅ done
```

The system now treats `stock_movements` as the real inventory source of truth.

```text
components.stock_qty = legacy/debug reference
stock_movements      = real physical stock truth
```

---

## 2. Core Inventory Doctrine

Rabbit is not a manufacturing system.

Rabbit does not:

```text
manufacture products
assemble products
consume child components when parent products are sold
treat relationships/BOM as physical inventory containment
```

Rabbit does:

```text
buy products directly
stock products directly
sell products directly
track physical stock entries and exits
use product relationships for navigation/intelligence only
```

Important rule:

```text
Only stock_movements changes physical inventory.
Product relationships do not affect stock.
```

Example:

```text
Client buys Pump XR200 x1
```

Rabbit creates:

```text
OUT movement for Pump XR200 x1
```

Rabbit does not automatically remove:

```text
Bearing 6205
Mechanical Seal
Bolts
Shaft
```

---

## 3. Stock Movement Core

Implemented files:

```text
app/Queries/StockMovementQuery.php
app/Repositories/StockMovementRepository.php
app/Forms/StoreStockMovementRequest.php
app/Services/StockService.php
app/Controllers/StockController.php
```

Implemented routes:

```http
GET  /stock/{componentId}
GET  /stock/{componentId}/movements
POST /stock/movements
```

Main behavior:

```text
Stock IN increases current stock.
Stock OUT decreases current stock.
Quantity is always positive.
type = in/out controls direction.
OUT movements are blocked when stock is insufficient.
Movement history works per product.
```

Stock calculation:

```sql
COALESCE(SUM(
    CASE
        WHEN type = 'in' THEN quantity
        WHEN type = 'out' THEN -quantity
        ELSE 0
    END
), 0)
```

---

## 4. Valid Stock Movement Reasons

### Stock entry reasons

Valid for:

```text
type = in
```

Reasons:

```text
INITIAL_STOCK
RFQ_ACCEPTED
PURCHASE_RECEIVED
RETURN
MANUAL_ADJUSTMENT
CANCELLED_ORDER_RESTORE
```

Meaning:

```text
INITIAL_STOCK
```

Used when migrating/importing old `components.stock_qty` into `stock_movements`.

```text
RFQ_ACCEPTED
```

Temporary/demo shortcut. Used when accepting an RFQ immediately increases stock.

Long-term replacement should be:

```text
PURCHASE_RECEIVED
```

because RFQ acceptance is a procurement decision, not physical receipt.

```text
PURCHASE_RECEIVED
```

Used when supplier goods physically arrive and are added to stock.

```text
RETURN
```

Used when a client returns a product and it goes back into stock.

```text
MANUAL_ADJUSTMENT
```

Used when owner/admin manually corrects stock upward.

```text
CANCELLED_ORDER_RESTORE
```

Used when stock was previously removed for an order, then the order is cancelled and stock must be restored.

### Stock exit reasons

Valid for:

```text
type = out
```

Reasons:

```text
SALE
DAMAGED
MANUAL_ADJUSTMENT
```

Meaning:

```text
SALE
```

Used when a customer order removes stock.

```text
DAMAGED
```

Used when damaged stock is removed from usable inventory.

```text
MANUAL_ADJUSTMENT
```

Used when owner/admin manually corrects stock downward.

---

## 5. Inventory V1

Implemented files:

```text
app/Queries/InventoryQuery.php
app/Repositories/InventoryRepository.php
app/Services/InventoryService.php
app/Controllers/InventoryController.php
```

Implemented routes:

```http
GET /inventory
GET /inventory/alerts
```

Inventory row fields:

```text
id
name
sku
category
legacy_stock_qty
current_stock
low_stock_threshold
preferred_supplier
preferred_unit_cost_mad
lead_time_days
stock_status
estimated_stock_value
```

Alert rows additionally include:

```text
recommended_action = CREATE_RFQ
```

Stock status logic:

```text
OUT_OF_STOCK if current_stock <= 0
LOW_STOCK    if current_stock <= low_stock_threshold
OK           otherwise
```

Estimated stock value:

```text
estimated_stock_value = current_stock × preferred_unit_cost_mad
```

If no preferred cost exists:

```text
estimated_stock_value = null
```

Inventory alerts return products where:

```text
current_stock <= low_stock_threshold
```

---

## 6. Orders Foundation

Existing database tables were reused:

```text
orders
order_items
```

Existing `orders` columns:

```text
id
client_id
status
stripe_payment_id
total_amount
shipping_address
notes
created_at
updated_at
```

Existing `orders.status` enum:

```text
pending
paid
processing
shipped
delivered
cancelled
```

Existing `order_items` columns:

```text
id
order_id
component_id
quantity
unit_price
supplier_id
created_at
```

Implemented files:

```text
app/Queries/OrderQuery.php
app/Repositories/OrderRepository.php
app/Forms/StoreOrderRequest.php
app/Forms/UpdateOrderStatusRequest.php
app/Services/OrderService.php
app/Controllers/OrderController.php
```

Implemented routes:

```http
GET   /orders
GET   /orders/{orderId}
POST  /orders
PATCH /orders/{orderId}/status
```

`line_total` is not stored in the DB. It is calculated dynamically:

```text
line_total = quantity × unit_price
```

---

## 7. Order → Stock OUT Integration

Working order-stock behavior:

```text
Order created
→ status = pending
→ no stock removed
```

```text
Order updated to processing
→ SALE stock OUT movement created
→ current_stock decreases
```

```text
Repeated processing update
→ no duplicate SALE movement
```

Stock movement created:

```text
type = out
reason = SALE
reference_type = order
reference_id = order id
quantity = order item quantity
```

Example stock movement data:

```json
{
  "component_id": 11,
  "type": "out",
  "quantity": 2,
  "reason": "SALE",
  "reference_type": "order",
  "reference_id": 7,
  "notes": "Stock removed for order #7"
}
```

Important rule:

```text
Stock OUT happens when order status becomes processing.
```

Not when the order is merely created.

---

## 8. Order Cancellation Restore

Implemented behavior:

```text
pending → cancelled
No stock movement.
```

Reason:

```text
Stock was never removed.
```

```text
pending → processing
Creates SALE stock OUT.
```

```text
processing → cancelled
Creates CANCELLED_ORDER_RESTORE stock IN.
```

```text
cancelled → cancelled again
No duplicate restore.
```

Restore stock movement:

```text
type = in
reason = CANCELLED_ORDER_RESTORE
reference_type = order
reference_id = order id
quantity = order item quantity
```

Example restore movement:

```json
{
  "component_id": 11,
  "type": "in",
  "quantity": 2,
  "reason": "CANCELLED_ORDER_RESTORE",
  "reference_type": "order",
  "reference_id": 7,
  "notes": "Stock restored after order #7 cancellation"
}
```

Important protection:

```text
Restore happens only if a SALE movement already exists for that order.
```

This prevents false stock inflation.

---

## 9. RFQ Lifecycle

Clean Rabbit RFQ lifecycle:

```text
draft → open → quoted → accepted
                  ↓
                rejected

open → expired
draft/open/quoted → cancelled
```

Recommended state machine:

```text
draft
  ├── open
  └── cancelled

open
  ├── quoted
  ├── expired
  └── cancelled

quoted
  ├── accepted
  ├── rejected
  └── expired

accepted
  └── final state

rejected
  └── final state

expired
  └── final state

cancelled
  └── final state
```

Meaning:

```text
draft
```

RFQ exists but is not officially sent/opened yet.

```text
open
```

RFQ has been sent to supplier. Supplier can respond.

```text
quoted
```

Supplier has submitted price, lead time, and notes.

```text
accepted
```

Buyer/owner accepted the quote.

```text
rejected
```

Buyer/owner rejected the quote.

```text
expired
```

RFQ deadline passed or was manually expired.

```text
cancelled
```

RFQ was cancelled before completion.

---

## 10. RFQ → Stock IN Integration

Temporary/demo behavior implemented:

```text
quoted → accepted
→ RFQ_ACCEPTED stock IN movement created
→ inventory increases
```

No other RFQ transition affects stock.

No stock movement for:

```text
draft → open
open → quoted
quoted → rejected
open → expired
draft/open/quoted → cancelled
```

Stock movement created:

```text
type = in
reason = RFQ_ACCEPTED
reference_type = rfq
reference_id = rfq id
quantity = quantity_requested
```

Example stock movement:

```json
{
  "component_id": 11,
  "type": "in",
  "quantity": 5,
  "reason": "RFQ_ACCEPTED",
  "reference_type": "rfq",
  "reference_id": 12,
  "notes": "Stock added after RFQ #12 acceptance"
}
```

Important implementation detail:

The repository `accept()` method returns a summary:

```php
[
    'rfq_id' => $rfqId,
    'previous_status' => 'quoted',
    'new_status' => 'accepted',
    'quoted_price' => ...,
    'revision_id' => ...,
    'decision_note' => ...,
    'decided_by' => ...
]
```

It does not return the full RFQ row.

Therefore, the service must fetch the full RFQ after acceptance:

```php
$result = $this->repository->accept(
    $request->rfqId(),
    $request->userId(),
    $request->decisionNote()
);

$rfq = $this->repository->findById($request->rfqId());

$this->createStockMovementAfterRfqAccepted($rfq);
```

The stock movement uses:

```php
$rfq['id']
$rfq['component_id']
$rfq['quantity_requested']
```

not:

```php
$rfq['quantity']
```

---

## 11. RFQ Stock Duplicate Protection

Added to `StockMovementQuery.php`:

```php
public static function existsByReferenceAndReason(): string
{
    return "
        SELECT id
        FROM stock_movements
        WHERE reference_type = ?
        AND reference_id = ?
        AND reason = ?
        LIMIT 1
    ";
}
```

Added to `StockMovementRepository.php`:

```php
public function existsByReferenceAndReason(
    string $referenceType,
    int $referenceId,
    string $reason
): bool {
    return $this
        ->query(
            StockMovementQuery::existsByReferenceAndReason(),
            [$referenceType, $referenceId, $reason]
        )
        ->exists();
}
```

Purpose:

```text
Prevent accepting the same RFQ from creating duplicate stock IN movements.
```

---

## 12. Final Confirmed Backend Loops

### Manual stock movement loop

```text
POST /stock/movements
→ stock movement recorded
→ GET /stock/{componentId} shows updated current_stock
```

### Inventory loop

```text
stock_movements
→ GET /inventory
→ calculated current_stock and stock_status
```

### Inventory alert loop

```text
current_stock <= low_stock_threshold
→ GET /inventory/alerts
→ recommended_action = CREATE_RFQ
```

### Order sale loop

```text
POST /orders
→ pending order

PATCH /orders/{id}/status processing
→ SALE stock OUT
→ inventory decreases
```

### Order cancellation loop

```text
processing order cancelled
→ CANCELLED_ORDER_RESTORE stock IN
→ inventory restored
```

### RFQ stock entry loop

```text
draft → open → quoted → accepted
→ RFQ_ACCEPTED stock IN
→ inventory increases
```

---

## 13. What Remains To Be Done

### Immediate next stage

Move to frontend/backend connection for the new inventory/order/stock workflows.

Recommended next stage:

```text
Frontend Integration V1
```

Priority:

```text
1. Inventory page/table
2. Inventory alert list
3. Stock detail panel
4. Stock movement timeline
5. Order list/detail/status actions
6. RFQ lifecycle action buttons
7. Low-stock alert → create RFQ shortcut
```

---

## 14. Backend Improvements Still Needed

### Authorization

Current routes are mostly protected by:

```text
AuthMiddleware
```

But some actions should be owner/admin only:

```text
POST /stock/movements
PATCH /orders/{id}/status
RFQ accept/reject/expire/open
Inventory/admin actions
```

Recommended next backend security work:

```text
OwnerMiddleware
RoleMiddleware
```

### Created by user

Currently some stock movements use:

```php
created_by = null
```

Later replace with authenticated user id:

```php
Auth::id()
```

or the equivalent custom session helper.

### RFQ accepted vs purchase received

Current shortcut:

```text
RFQ_ACCEPTED creates stock IN
```

Long-term correct flow:

```text
RFQ accepted
→ purchase expected
→ goods physically received
→ PURCHASE_RECEIVED stock IN
```

Future module:

```text
Purchase Reception
```

### Stock reservation

Current behavior:

```text
Stock OUT happens at processing.
```

Future improvement:

```text
paid/pending order may reserve stock.
processing removes/reserves final stock.
cancelled releases reservation.
```

This should not be implemented yet.

---

## 15. Do Not Implement Yet

Do not implement these until frontend/core workflows are stable:

```text
Forecasting
ABC classification
CMUP valuation
Stripe
AI assistant
Advanced KPIs
Automatic procurement
Complex dashboards
```

Correct priority:

```text
Stock truth first.
Inventory visibility second.
Orders/RFQ integration third.
Frontend connection fourth.
Advanced intelligence later.
```

---

## 16. Next Stage Recommendation

The next best stage is:

```text
Frontend Integration V1
```

Because the backend now has enough real functionality to connect to the UI:

```text
Inventory
Stock movements
Orders
RFQ lifecycle
Alerts
```

Recommended frontend screens/components:

```text
Owner dashboard inventory summary cards
Inventory table
Low-stock alerts panel
Product stock detail modal
Stock movement timeline
Order management table
Order detail view
RFQ action buttons based on GET /rfqs/{id}/actions
```

Minimum useful frontend loop:

```text
Owner opens inventory page
→ sees current stock from /inventory
→ sees low-stock alerts from /inventory/alerts
→ opens product stock timeline from /stock/{id}/movements
→ creates RFQ for low-stock item
→ opens/quotes/accepts RFQ
→ inventory stock increases
```
