Inventory Layer — Current Status
Status

The inventory layer has been implemented and tested.

It now exposes calculated inventory data using the movement-based stock system.

Core rule:

Inventory does not read real stock from components.stock_qty.
Inventory calculates real stock from stock_movements.

Current stock formula:

current_stock = total IN movements - total OUT movements
Implemented Files

The inventory layer follows the existing Rabbit architecture:

Controller
→ Service
→ Repository
→ Query
→ Database

Implemented files:

app/Queries/InventoryQuery.php
app/Repositories/InventoryRepository.php
app/Services/InventoryService.php
app/Controllers/InventoryController.php
Implemented Routes

The inventory API currently supports:

GET /inventory
GET /inventory/alerts

Both routes are protected by authentication middleware.

Controller Fix Completed

A response bug was found and fixed.

Problem:

The controller method returned void, so the router encoded null.

Correct behavior:

Controller methods must return an array response.

Correct response helper namespace:

use App\Support\ApiResponse;

Correct response format:

return ApiResponse::success('Inventory fetched successfully', $items);

Reason:

ApiResponse::success() expects:
success(string $message, mixed $data = null): array
Endpoint Behavior
GET /inventory

Purpose:

Return inventory status for all active products/components.

Returned information:

id
name
sku
category
legacy_stock_qty
low_stock_threshold
current_stock
preferred_supplier
preferred_unit_cost_mad
lead_time_days
stock_status
estimated_stock_value
GET /inventory/alerts

Purpose:

Return products/components that need inventory attention.

Alert rule:

current_stock <= low_stock_threshold

Returned items include:

stock_status
recommended_action

Default recommended action:

CREATE_RFQ
Stock Status Logic

Inventory status is calculated from current stock and low-stock threshold:

OUT_OF_STOCK if current_stock <= 0
LOW_STOCK    if current_stock <= low_stock_threshold
OK           otherwise
Estimated Stock Value

Estimated inventory value is calculated using the preferred supplier unit cost:

estimated_stock_value = current_stock × preferred_supplier_unit_cost_mad

If no preferred supplier source exists:

preferred_supplier = null
preferred_unit_cost_mad = null
lead_time_days = null
estimated_stock_value = null

This is valid behavior, not a bug.

Completed Milestone

The following milestone is now complete:

GET /inventory works
GET /inventory/alerts works
Inventory reads calculated stock from stock_movements
Inventory exposes stock_status
Inventory exposes estimated_stock_value
Inventory alerts detect low/out-of-stock items
Inventory alerts recommend CREATE_RFQ
ApiResponse usage has been corrected
Current Project State

The completed backend loop now includes:

Products
Product relationships
Supplier sources
RFQs
Stock movements
Inventory summary
Inventory alerts

The stock/inventory doctrine is now stable:

product relationships = why products are connected
stock movements        = what physically happened to inventory
inventory              = current interpreted stock state
Next Item

The next logical item is:

Connect inventory alerts to RFQ creation workflow.

Goal:

A low-stock product should guide the owner toward creating an RFQ.

V1 behavior should stay simple:

Inventory alert recommends CREATE_RFQ.
Owner manually creates RFQ.
No automatic RFQ creation yet.

Next implementation target:

Improve RFQ creation so it can be launched cleanly from an inventory alert/prod