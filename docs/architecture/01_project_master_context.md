# Rabbit — Master Project Context

## Project Identity

Rabbit is a hybrid industrial backend system combining:

- Procurement / RFQ management
- E-commerce product/order flow
- Supplier management
- Inventory and stock movement tracking
- Product relationship intelligence
- Future cost intelligence, forecasting, and AI assistant features

Domain: industrial MRO — Maintenance, Repair, Operations.

Typical products:

- pumps
- motors
- bearings
- seals
- shafts
- hydraulic components
- fasteners
- assemblies
- sub-assemblies
- raw materials

Default currency for monetary values: **MAD**.

---

## Core Business Rule

Rabbit is **not** a production or assembly system.

Rabbit does not manufacture products, assemble pumps, or consume child components to produce parent products.

Rabbit buys, stocks, and sells all product levels independently:

- assemblies
- sub-assemblies
- components
- raw materials

Example:

```text
Pump XR200 stock       = independent stock
Bearing 6205 stock     = independent stock
Seal Kit stock         = independent stock
Motor Unit stock       = independent stock
```

Selling a pump does not reduce bearing stock.

Selling a bearing does not affect pump stock.

---

## Source of Truth Rules

```text
components
= buyable/sellable/stockable product records

part_sources
= supplier pricing, lead times, MOQ, preferred supplier info

dependencies
= product relationship intelligence, not manufacturing BOM

stock_movements
= physical inventory history and future stock source of truth

rfq_requests / rfq_revisions
= procurement workflow

orders / order_items
= e-commerce sales workflow
```

---

## Backend Architecture

Current backend pattern:

```text
Controller
→ Form Request
→ Service
→ Repository
→ Query
→ Database
```

New stock movement layer follows the same pattern:

```text
StockController
StockService
StockMovementRepository
StockMovementQuery
StoreStockMovementRequest
```

---

## Immediate Core Loop Target

```text
Product
→ Supplier source
→ RFQ
→ Supplier quote
→ Accepted quote
→ Stock IN
→ Order
→ Stock OUT
→ Inventory summary
→ Low-stock / RFQ recommendation
```

Complete this loop before advanced forecasting or AI.
