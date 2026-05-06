Below is the updated documentation section you can paste into the Rabbit master context.

# Rabbit Backend Update — Stock Alert Notifications Implemented

## Status

Stock alert notifications are now implemented and tested successfully.

Completed notification types:

```text
LOW_STOCK_ALERT
OUT_OF_STOCK_ALERT

These were added after the RFQ and purchase lot notification triggers. The notification system now supports workflow notifications and stock risk alerts.

Purpose

The stock alert notification feature warns the owner when a product enters a risky stock state.

The system does not notify on every stock movement.

It only notifies when a stock movement causes the product stock status to cross into:

LOW_STOCK
OUT_OF_STOCK

This follows the Rabbit notification philosophy:

Notify only for action required, workflow handoff, risk/alert, or important user-facing status change.
Files Modified

Main file modified:

app/Services/StockService.php

Existing dependencies used:

App\Services\NotificationService
App\Repositories\StockMovementRepository
App\Repositories\ProductRepository

No new database table was required.

No new route was required.

No notification job/queue was added.

Implementation Location

The logic was added inside:

StockService::recordMovement()

This is the correct place because all manual stock movement creation passes through this method.

Flow:

1. Get product.
2. Calculate stock before movement.
3. Resolve stock status before movement.
4. Validate OUT movement does not exceed available stock.
5. Insert stock movement.
6. Recalculate stock after movement.
7. Resolve stock status after movement.
8. Notify owner only if product crossed into LOW_STOCK or OUT_OF_STOCK.
Stock Status Resolution

Rabbit uses this stock status logic:

private function resolveStockStatus(float $currentStock, float $threshold): string
{
    if ($currentStock <= 0) {
        return 'OUT_OF_STOCK';
    }

    if ($currentStock <= $threshold) {
        return 'LOW_STOCK';
    }

    return 'OK';
}

Status meanings:

OK = current stock is above low stock threshold
LOW_STOCK = current stock is positive but less than or equal to threshold
OUT_OF_STOCK = current stock is zero or below

Important distinction:

Stock dashboard statuses:
OK
LOW_STOCK
OUT_OF_STOCK

Product catalog statuses:
ok
low
out

The stock alert system uses dashboard/internal stock statuses:

OK
LOW_STOCK
OUT_OF_STOCK
Notification Trigger Rules

The implemented transition rules are:

OK → LOW_STOCK
creates LOW_STOCK_ALERT

OK → OUT_OF_STOCK
creates OUT_OF_STOCK_ALERT

LOW_STOCK → OUT_OF_STOCK
creates OUT_OF_STOCK_ALERT

The following transitions intentionally do not notify:

LOW_STOCK → LOW_STOCK
OUT_OF_STOCK → OUT_OF_STOCK
OUT_OF_STOCK → LOW_STOCK
LOW_STOCK → OK
OUT_OF_STOCK → OK
OK → OK

Reason:

The V1 goal is to notify only when stock enters a risk state, not when it remains risky or recovers.

Optional future notification:

STOCK_RECOVERED

Not implemented in V1.

Notification Recipient

Stock alerts are sent to:

role = owner

Using:

NotificationService::notifyRole()

This means all owner users can see stock alert notifications.

Known V1 limitation still applies:

Role-level notifications have shared read state.
If one owner marks the notification as read, it becomes read for all owners.
This is acceptable for school project V1.
Notification Types Added
LOW_STOCK_ALERT

Triggered when product enters low stock.

Example title:

Stock faible

Example message:

Le produit Pompe hydraulique aluminium 11 cc/rev (CMP-HYD-GP11) est passé en stock faible. Stock actuel: 2. Seuil: 2.

Reference:

reference_type = product
reference_id = product id
OUT_OF_STOCK_ALERT

Triggered when product enters out-of-stock state.

Example title:

Rupture de stock

Example message:

Le produit Pompe hydraulique aluminium 11 cc/rev (CMP-HYD-GP11) est maintenant en rupture de stock. Stock actuel: 0.

Reference:

reference_type = product
reference_id = product id
Final StockService Logic

The final implementation shape:

$beforeStock = $this->repository->currentStock($componentId);

$beforeStatus = $this->resolveStockStatus(
    $beforeStock,
    (float) $product['low_stock_threshold']
);

// create stock movement

$afterStock = $this->repository->currentStock($componentId);

$afterStatus = $this->resolveStockStatus(
    $afterStock,
    (float) $product['low_stock_threshold']
);

$this->notifyStockAlertIfNeeded(
    $product,
    $beforeStatus,
    $afterStatus,
    $afterStock
);

Private helper added:

private function notifyStockAlertIfNeeded(
    array $product,
    string $beforeStatus,
    string $afterStatus,
    float $currentStock
): void

This helper exits early if:

beforeStatus === afterStatus

or if the new status is not:

LOW_STOCK
OUT_OF_STOCK
Test Product Used

Test product:

{
  "id": 25,
  "name": "Pompe hydraulique aluminium 11 cc/rev",
  "sku": "CMP-HYD-GP11",
  "category": "component",
  "unit_of_measure": "ea",
  "stock_qty": 4,
  "low_stock_threshold": 2,
  "stock_status": "ok",
  "matched_source": {
    "unit_cost": 2145,
    "supplier_id": 3,
    "supplier_name": "Casatech Hydraulique"
  }
}

Initial condition:

current stock = 4
low stock threshold = 2
status = OK
Test 1 — LOW_STOCK_ALERT

Request:

POST /stock/movements

Body:

{
  "component_id": 25,
  "type": "out",
  "quantity": 2,
  "reason": "MANUAL_ADJUSTMENT",
  "reference_type": "manual",
  "reference_id": null,
  "notes": "Test low stock alert"
}

Expected transition:

Before: 4 → OK
After: 2 → LOW_STOCK

Expected notification:

LOW_STOCK_ALERT

Result:

Test passed.
Test 2 — OUT_OF_STOCK_ALERT

Request:

POST /stock/movements

Body:

{
  "component_id": 25,
  "type": "out",
  "quantity": 2,
  "reason": "MANUAL_ADJUSTMENT",
  "reference_type": "manual",
  "reference_id": null,
  "notes": "Test out of stock alert"
}

Expected transition:

Before: 2 → LOW_STOCK
After: 0 → OUT_OF_STOCK

Expected notification:

OUT_OF_STOCK_ALERT

Result:

Test passed.
Verification Endpoints

Stock verification:

GET /stock/25

Notification verification:

GET /notifications

Unread count verification:

GET /notifications/unread-count
Current Notification System Status

Implemented and tested:

RFQ_ASSIGNED
RFQ_QUOTED
RFQ_ACCEPTED_BY_OWNER
PURCHASE_LOT_NEEDS_FINALIZATION
PURCHASE_LOT_FINALIZED
LOW_STOCK_ALERT
OUT_OF_STOCK_ALERT

Prepared/intended but still needs testing or implementation:

RFQ_REJECTED_BY_OWNER
ORDER_CREATED
ORDER_STATUS_CHANGED

Skipped intentionally:

RFQ_CREATED
RFQ_OPENED
RFQ_EXPIRED
PURCHASE_LOT_DRAFT_CREATED_FROM_RFQ
STOCK_IN_PURCHASE_RECEIVED

Reason for skipping STOCK_IN_PURCHASE_RECEIVED:

It is redundant because PURCHASE_LOT_FINALIZED already tells the owner that stock was received through purchase lot finalization.