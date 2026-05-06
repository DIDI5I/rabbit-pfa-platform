# Rabbit — Stock Movement Implementation

## Status

Implementation started.

The old `stock_movements` table existed but needed correction.

Old table:

```text
id
component_id
type ENUM('in','out','adjustment')
quantity
reason VARCHAR(255)
reference_id
created_by
created_at
```

Decision:

```text
Drop and reconstruct the table
```

Reason:

- stock movement layer was not finalized
- previous rows were test data
- old `adjustment` type was unclear
- `reason` should be controlled

---

## Final Stock Movement Table

```sql
DROP TABLE IF EXISTS stock_movements;

CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,

    component_id INT NOT NULL,

    type ENUM('in', 'out') NOT NULL,

    quantity DECIMAL(10,4) NOT NULL,

    reason ENUM(
        'INITIAL_STOCK',
        'RFQ_ACCEPTED',
        'PURCHASE_RECEIVED',
        'SALE',
        'MANUAL_ADJUSTMENT',
        'RETURN',
        'DAMAGED',
        'CANCELLED_ORDER_RESTORE'
    ) NOT NULL,

    reference_type VARCHAR(50) NULL,
    reference_id INT NULL,

    notes TEXT NULL,

    created_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_stock_movements_component
        FOREIGN KEY (component_id) REFERENCES components(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_stock_movements_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL,

    CONSTRAINT chk_stock_movements_quantity_positive
        CHECK (quantity > 0),

    INDEX idx_stock_movements_component (component_id),
    INDEX idx_stock_movements_type (type),
    INDEX idx_stock_movements_reason (reason),
    INDEX idx_stock_movements_created_at (created_at),
    INDEX idx_stock_movements_reference (reference_type, reference_id)
);
```

---

## Core Rule

```text
quantity is always positive
type controls direction
```

Correct:

```text
type = out
quantity = 5
```

Wrong:

```text
quantity = -5
```

---

## Allowed Reasons

```text
INITIAL_STOCK
RFQ_ACCEPTED
PURCHASE_RECEIVED
SALE
MANUAL_ADJUSTMENT
RETURN
DAMAGED
CANCELLED_ORDER_RESTORE
```

Technical enum values stay in English.

French display labels:

```php
$reasonLabels = [
    'INITIAL_STOCK' => 'Stock initial',
    'RFQ_ACCEPTED' => 'RFQ acceptée',
    'PURCHASE_RECEIVED' => 'Réception fournisseur',
    'SALE' => 'Vente client',
    'MANUAL_ADJUSTMENT' => 'Ajustement manuel',
    'RETURN' => 'Retour',
    'DAMAGED' => 'Stock endommagé',
    'CANCELLED_ORDER_RESTORE' => 'Restauration après annulation de commande',
];
```

---

## Backend Files

```text
app/Queries/StockMovementQuery.php
app/Repositories/StockMovementRepository.php
app/Forms/StoreStockMovementRequest.php
app/Services/StockService.php
app/Controllers/StockController.php
```

Routes:

```text
GET  /stock/{componentId}
GET  /stock/{componentId}/movements
POST /stock/movements
```

---

## StockMovementQuery

Methods:

```text
insert
currentStock
findByComponent
stockSummary
```

Important SQL:

```sql
SELECT
    COALESCE(SUM(
        CASE
            WHEN type = 'in' THEN quantity
            WHEN type = 'out' THEN -quantity
            ELSE 0
        END
    ), 0) AS current_stock
FROM stock_movements
WHERE component_id = ?;
```

---

## StockMovementRepository

Methods:

```text
create(array $data): void
currentStock(int $componentId): float
findByComponent(int $componentId): array
stockSummary(int $componentId): ?array
```

---

## StoreStockMovementRequest

Validation:

```text
component_id required + integer
type required + in/out
quantity required + numeric + > 0
reason required + allowed reason
reason must match movement direction
```

IN-compatible reasons:

```text
INITIAL_STOCK
RFQ_ACCEPTED
PURCHASE_RECEIVED
RETURN
MANUAL_ADJUSTMENT
CANCELLED_ORDER_RESTORE
```

OUT-compatible reasons:

```text
SALE
DAMAGED
MANUAL_ADJUSTMENT
```

---

## StockService

Methods:

```text
recordMovement()
getStock()
getMovements()
```

Rules:

```text
component must exist
OUT cannot make stock negative
stock status is resolved from current stock and low_stock_threshold
```

Statuses:

```text
OUT_OF_STOCK
LOW_STOCK
OK
```

---

## StockController

Methods:

```text
show(int $componentId)
movements(int $componentId)
storeMovement()
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

---

## Test Requests

Initial stock:

```json
{
  "component_id": 11,
  "type": "in",
  "quantity": 48,
  "reason": "INITIAL_STOCK",
  "reference_type": "opening_stock",
  "reference_id": null,
  "notes": "Initial stock migration from components.stock_qty"
}
```

Sale:

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

Insufficient stock error:

```json
{
  "error": "Validation failed",
  "fields": {
    "stock": [
      "Insufficient stock for this OUT movement."
    ]
  }
}
```
