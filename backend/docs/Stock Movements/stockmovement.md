Stock Movement Layer — Current Status
Status

The stock movement layer has been implemented and tested.

This layer is now responsible for recording real physical inventory changes in Rabbit.

Core rule:

stock_movements = real physical inventory history
components.stock_qty = legacy/debug stock only

Product relationships do not affect stock.

Selling one product only decreases the stock of that exact product.

Example:

Selling Pump XR200 decreases Pump XR200 stock only.
It does not decrease Bearing 6205 stock, even if Bearing 6205 is related to Pump XR200.

Rabbit is not a manufacturing or assembly system. It buys, stocks, and sells every product level independently:

assemblies
sub-assemblies
components
raw materials
Implemented Files

The stock movement layer follows the existing Rabbit backend architecture:

Controller
→ Form Request
→ Service
→ Repository
→ Query
→ Database

Current files:

app/Queries/StockMovementQuery.php
app/Repositories/StockMovementRepository.php
app/Forms/StoreStockMovementRequest.php
app/Services/StockService.php
app/Controllers/StockController.php
Implemented Routes

The stock API currently supports:

POST /stock/movements
GET  /stock/{componentId}
GET  /stock/{componentId}/movements

All routes are protected by authentication middleware.

Movement Rules
Quantity Rule

Quantity is always positive.

Correct:

{
  "type": "out",
  "quantity": 5
}

Wrong:

{
  "type": "out",
  "quantity": -5
}

The movement type controls the direction.

Movement Types

There are two physical stock movement types:

in
out
in

Means stock increases.

Used for:

initial stock
purchase received
RFQ accepted stock entry
return
manual positive adjustment
cancelled order restore
out

Means stock decreases.

Used for:

sale
damaged stock
manual negative adjustment
Movement Reasons

Technical enum values are kept in English.

French display labels should be handled in the UI or mapping layer.

Example:

INITIAL_STOCK              → Stock initial
RFQ_ACCEPTED               → RFQ acceptée
PURCHASE_RECEIVED          → Réception fournisseur
SALE                       → Vente client
MANUAL_ADJUSTMENT          → Ajustement manuel
RETURN                     → Retour
DAMAGED                    → Stock endommagé
CANCELLED_ORDER_RESTORE    → Restauration après annulation de commande

Do not use accented labels directly as database enum values.

Tested Behavior
Initial Stock Movement

Tested behavior:

An IN movement with reason INITIAL_STOCK correctly increases current stock.

Example:

Initial stock movement: +48
Current stock becomes 48
Sale / OUT Movement

Tested behavior:

An OUT movement with reason SALE correctly decreases current stock.

Example:

Initial stock: 48
Sale movement: -10

Current stock = 38
Current Stock Formula

Current stock is calculated from the movement history:

current_stock = total IN movements - total OUT movements

Expanded:

current_stock =
sum(in quantities) - sum(out quantities)

Important:

current_stock is not read directly from components.stock_qty.
Insufficient Stock Protection

The service layer prevents OUT movements that would make stock negative.

Example:

Current stock: 5
Requested OUT movement: 10

Result: rejected
Reason: insufficient stock

Expected error meaning:

Insufficient stock for this OUT movement.

This confirms that inventory integrity is protected in the service layer.

Endpoint Behavior
POST /stock/movements

Purpose:

Record a physical stock movement.

Used for:

initial stock
purchase received
RFQ accepted stock entry
sale
manual adjustment
return
damaged stock
cancelled order restore
GET /stock/{componentId}

Purpose:

Return current stock summary for one product/component.

Expected information:

product id
name
sku
category
legacy stock quantity
low stock threshold
current stock
stock status
GET /stock/{componentId}/movements

Purpose:

Return stock movement history for one product/component.

Expected information:

movement id
product/component id
product/component name
sku
movement type
quantity
reason
reference type
reference id
notes
created by
creation date

Movement history should be shown from newest to oldest.

Stock Status Logic

Stock status is resolved from calculated current stock and the product’s low-stock threshold.

OUT_OF_STOCK if current_stock <= 0
LOW_STOCK    if current_stock <= low_stock_threshold
OK           otherwise
Important Design Decision

Rabbit now clearly separates three concepts:

Product relationships
Physical stock movements
Procurement/order actions

Meaning:

product relationships = why products are connected
stock movements        = what physically happened to inventory
RFQs/orders            = business/procurement/sales workflow

This prevents a major design mistake:

Do not use product relationships as BOM stock consumption.

Product relationships are used for:

technical structure
replacement parts
compatible parts
spare parts
accessories
related products
navigation
cost understanding
procurement intelligence

They are not used to automatically consume child stock.

Completed Milestone

The following milestone is complete:

POST /stock/movements works
GET /stock/{componentId} works
GET /stock/{componentId}/movements works
IN movements increase stock
OUT movements decrease stock
OUT movements cannot create negative stock
current stock is calculated from stock movements
components.stock_qty is no longer the real source of truth
Next Immediate Step

Now that stock movements are tested, the next step is:

Migrate existing components.stock_qty into INITIAL_STOCK movements.

Conceptually:

For every product with existing legacy stock:
    create one INITIAL_STOCK movement
    using the old components.stock_qty value

After migration:

stock_movements becomes the real inventory base
components.stock_qty remains only legacy/debug/reference data
Next Development Layer

After stock migration, continue to the inventory layer:

InventoryQuery
InventoryRepository
InventoryService
InventoryController

Planned endpoints:

GET /inventory
GET /inventory/alerts

Inventory v1 should return:

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
Inventory Formulas

Estimated stock value:

estimated_stock_value = current_stock × preferred_supplier_unit_cost

Low-stock alert rule:

current_stock <= low_stock_threshold

Recommended action for low-stock items:

CREATE_RFQ
Current Priority

Do not jump yet to:

forecasting
AI assistant
dashboard polish
ABC classification
CMUP valuation
advanced procurement automation

Current priority:

Finish the core inventory loop first.

Core loop:

Product
→ Supplier source
→ RFQ
→ Accepted quote / purchase received
→ Stock IN
→ Order
→ Stock OUT
→ Inventory dashboard