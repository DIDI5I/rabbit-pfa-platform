# Rabbit — Stock Movement Implementation Documentation

## 1. Goal

We implemented the first real inventory foundation for Rabbit:

```text
stock_movements = source of truth for current stock
components.stock_qty = legacy/debug reference only
```

Rabbit does **not** consume child components when selling a parent product.

Product relationships are only for:

```text
technical structure
compatibility
replacement parts
accessories
related products
procurement intelligence
cost intelligence
UI navigation
future AI reasoning
```

Physical inventory changes only through `stock_movements`.

---

## 2. Core Stock Doctrine

Rabbit is **not** a manufacturing system.

Rabbit does **not**:

```text
manufacture products
assemble products
consume child components when parent products are sold
treat relationships/BOM as physical inventory containment
```

Rabbit **does**:

```text
buy products directly
stock products directly
sell products directly
track physical stock entries and exits
use relationships for navigation and intelligence
```

Every product is independently stockable:

```text
assembly stock       = independent
sub_assembly stock   = independent
component stock      = independent
raw_material stock   = independent
```

Example:

```text
Pump XR200 stock   = independent
Bearing 6205 stock = independent
Seal Kit stock     = independent
Motor Unit stock   = independent
```

If a client buys:

```text
Pump XR200 x1
```

Rabbit should create:

```text
OUT movement for Pump XR200 x1
```

It should **not** automatically reduce:

```text
Bearing 6205
Mechanical Seal
Bolt M10
```

---

## 3. Database Table

The table `stock_movements` already existed and matched the target schema.

Important fields:

```text
id
component_id
type
quantity
reason
reference_type
reference_id
notes
created_by
created_at
```

Core rule:

```text
quantity is always positive
type controls direction
```

Examples:

```text
type = in,  quantity = 50  → adds 50 to stock
type = out, quantity = 10  → removes 10 from stock
```

Invalid design avoided:

```text
quantity = -10
```

---

## 4. Valid Movement Types

```text
in
out
```

---

## 5. Stock Entry Reasons

Valid reasons for:

```text
type = in
```

are:

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

Used when importing old `components.stock_qty` values into `stock_movements`.

```text
RFQ_ACCEPTED
```

Temporary/demo shortcut. Used when accepting an RFQ immediately increases stock.

Long-term, this should likely be replaced by:

```text
PURCHASE_RECEIVED
```

because RFQ acceptance is a commercial/procurement decision, while stock should increase only when goods physically arrive.

```text
PURCHASE_RECEIVED
```

Best real-world reason for stock entry. Used when supplier goods physically arrive and are added to inventory.

```text
RETURN
```

Used when a client returns a product and it goes back into stock.

```text
MANUAL_ADJUSTMENT
```

Used when the owner/admin manually corrects stock upward.

```text
CANCELLED_ORDER_RESTORE
```

Used when an order was cancelled after stock had already been removed/reserved, so the quantity is restored.

Most important real stock-entry reason:

```text
PURCHASE_RECEIVED
```

---

## 6. Stock Exit Reasons

Valid reasons for:

```text
type = out
```

are:

```text
SALE
DAMAGED
MANUAL_ADJUSTMENT
```

Meaning:

```text
SALE
```

Used when a client order/product sale removes stock.

```text
DAMAGED
```

Used when damaged stock is removed from usable inventory.

```text
MANUAL_ADJUSTMENT
```

Used when the owner/admin manually corrects stock downward.

---

## 7. Files Created

We created the stock movement backend layer using Rabbit’s architecture:

```text
Controller → Form Request → Service → Repository → Query → Database
```

Files:

```text
app/Queries/StockMovementQuery.php
app/Repositories/StockMovementRepository.php
app/Forms/StoreStockMovementRequest.php
app/Services/StockService.php
app/Controllers/StockController.php
```

---

## 8. `StockMovementQuery.php`

Purpose:

```text
Contains raw SQL only.
No business logic.
```

Methods added:

```text
insert()
currentStock()
findByComponent()
stockSummary()
```

Responsibilities:

```text
insert()          → inserts a movement
currentStock()    → calculates stock from IN - OUT
findByComponent() → gets movement history for one product
stockSummary()    → gets product info + calculated stock
```

Current stock formula:

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

## 9. `StockMovementRepository.php`

Purpose:

```text
Executes stock movement queries.
Casts DB values to correct PHP types.
Returns arrays to the service layer.
```

Methods added:

```text
create(array $data): void
currentStock(int $componentId): float
findByComponent(int $componentId): array
stockSummary(int $componentId): ?array
```

Important casting:

```php
$row['id'] = (int) $row['id'];
$row['component_id'] = (int) $row['component_id'];
$row['quantity'] = (float) $row['quantity'];
$row['reference_id'] = isset($row['reference_id']) ? (int) $row['reference_id'] : null;
$row['created_by'] = isset($row['created_by']) ? (int) $row['created_by'] : null;
```

---

## 10. `StoreStockMovementRequest.php`

Purpose:

```text
Validates and normalizes incoming stock movement data.
```

Required fields:

```text
component_id
type
quantity
reason
```

Optional fields:

```text
reference_type
reference_id
notes
```

Validation rules:

```text
component_id required integer
type required and must be in/out
quantity required numeric > 0
reason required and must be allowed
reason must match movement type
```

Protected invalid case:

```json
{
  "type": "out",
  "reason": "PURCHASE_RECEIVED"
}
```

This is rejected because `PURCHASE_RECEIVED` is only valid for stock entry.

---

## 11. `StockService.php`

Purpose:

```text
Contains business rules for stock movement operations.
```

Methods added:

```text
recordMovement(StoreStockMovementRequest $request, ?int $createdBy = null): array
getStock(int $componentId): array
getMovements(int $componentId): array
resolveStockStatus(float $currentStock, float $threshold): string
```

Business rules implemented:

```text
Product must exist before recording movement.
OUT movement cannot exceed current stock.
Stock status is calculated from current stock and low stock threshold.
```

Insufficient stock protection:

```php
if ($currentStock < $request->quantity()) {
    throw new ValidationException([
        'stock' => ['Insufficient stock for this OUT movement.'],
    ]);
}
```

Stock status logic:

```text
OUT_OF_STOCK if current_stock <= 0
LOW_STOCK    if current_stock <= low_stock_threshold
OK           otherwise
```

---

## 12. `StockController.php`

Purpose:

```text
Handles HTTP calls and delegates work to StockService.
```

Methods added:

```text
show(int $componentId): array
movements(int $componentId): array
storeMovement(): array
```

Current implementation note:

```php
return $service->recordMovement($request, null);
```

Currently `created_by` is `null`.

Later replace with authenticated user id:

```php
Auth::id()
```

or whatever the custom auth/session helper uses.

---

## 13. Routes Added

Import required:

```php
use App\Controllers\StockController;
```

Routes:

```php
$router->getRoute('/stock/{componentId}', [StockController::class, 'show'])
       ->only([AuthMiddleware::class]);

$router->getRoute('/stock/{componentId}/movements', [StockController::class, 'movements'])
       ->only([AuthMiddleware::class]);

$router->postRoute('/stock/movements', [StockController::class, 'storeMovement'])
       ->only([AuthMiddleware::class]);
```

Current access rule:

```text
Authenticated users can test stock routes.
```

Later improvement:

```text
POST /stock/movements should become owner-only.
```

---

## 14. Endpoints Implemented

### Get stock summary

```http
GET /stock/{componentId}
```

Example:

```http
GET /stock/11
```

Expected response:

```json
{
  "message": "Stock fetched successfully",
  "data": {
    "id": 11,
    "name": "Product name",
    "sku": "SKU",
    "category": "component",
    "legacy_stock_qty": 48,
    "low_stock_threshold": 15,
    "current_stock": 48,
    "stock_status": "OK"
  }
}
```

---

### Get movement history

```http
GET /stock/{componentId}/movements
```

Example:

```http
GET /stock/11/movements
```

Expected response:

```json
{
  "message": "Stock movements fetched successfully",
  "data": [
    {
      "id": 1,
      "component_id": 11,
      "component_name": "Product name",
      "component_sku": "SKU",
      "type": "in",
      "quantity": 48,
      "reason": "INITIAL_STOCK",
      "reference_type": "opening_stock",
      "reference_id": null,
      "notes": "Initial stock migration test",
      "created_by": null,
      "created_by_name": null,
      "created_at": "2026-..."
    }
  ]
}
```

---

### Create stock movement

```http
POST /stock/movements
Content-Type: application/json
```

Example stock entry:

```json
{
  "component_id": 11,
  "type": "in",
  "quantity": 48,
  "reason": "INITIAL_STOCK",
  "reference_type": "opening_stock",
  "reference_id": null,
  "notes": "Initial stock migration test"
}
```

Expected response:

```json
{
  "message": "Stock movement recorded successfully"
}
```

Example stock exit:

```json
{
  "component_id": 11,
  "type": "out",
  "quantity": 10,
  "reason": "SALE",
  "reference_type": "order",
  "reference_id": 1,
  "notes": "Client order test"
}
```

---

## 15. Tests Completed

Confirmed working:

```text
POST /stock/movements with IN movement
GET /stock/{id}
GET /stock/{id}/movements
POST /stock/movements with OUT movement
GET /stock/{id} again
```

Expected stock behavior confirmed:

```text
Initial stock entry adds stock.
Sale movement removes stock.
Current stock is calculated from stock_movements.
```

---

## 16. Initial Stock Migration

Migration SQL:

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

This prevents duplicate initial stock rows.

After migration:

```text
components.stock_qty = legacy/debug
stock_movements = real current stock
```

Verification SQL:

```sql
SELECT
    c.id,
    c.name,
    c.sku,
    c.stock_qty AS legacy_stock_qty,
    COALESCE(SUM(
        CASE
            WHEN sm.type = 'in' THEN sm.quantity
            WHEN sm.type = 'out' THEN -sm.quantity
            ELSE 0
        END
    ), 0) AS movement_stock
FROM components c
LEFT JOIN stock_movements sm ON sm.component_id = c.id
GROUP BY
    c.id,
    c.name,
    c.sku,
    c.stock_qty
ORDER BY c.id;
```

---

## 17. Current System State

Completed:

```text
Stock movement schema verified
StockMovementQuery created
StockMovementRepository created
StoreStockMovementRequest created
StockService created
StockController created
Stock routes added
Stock movement endpoints tested successfully
```

Current backend capability:

```text
Rabbit can record physical inventory IN/OUT movements.
Rabbit can calculate real current stock from movement history.
Rabbit can reject OUT movements when stock is insufficient.
Rabbit can show stock history per product.
```

---

## 18. Next Stage

Next stage:

```text
Inventory V1
```

Target endpoints:

```http
GET /inventory
GET /inventory/alerts
```

Inventory V1 should return:

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

Low-stock alerts should return products where:

```text
current_stock <= low_stock_threshold
```

With:

```text
recommended_action = CREATE_RFQ
```

Next implementation files:

```text
app/Queries/InventoryQuery.php
app/Repositories/InventoryRepository.php
app/Services/InventoryService.php
app/Controllers/InventoryController.php
```

---

## 19. Immediate Next Development Order

Recommended next order:

```text
1. Create InventoryQuery
2. Create InventoryRepository
3. Create InventoryService
4. Create InventoryController
5. Add routes
6. Test GET /inventory
7. Test GET /inventory/alerts
8. Then connect inventory alerts to RFQ creation logic
```

Do not implement forecasting, ABC classification, CMUP, AI, or advanced KPIs until Inventory V1 works.
