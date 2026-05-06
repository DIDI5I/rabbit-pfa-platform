# RFQ Data Model

## 🎯 Overview

The RFQ system is mainly built around the `rfq_requests` table.

An RFQ represents a procurement request from the company owner to a supplier for a specific component.

---

## 📦 Main Table: `rfq_requests`

### Purpose

Stores supplier quotation requests.

Each row represents one RFQ for one component.

---

## 🧱 Important Fields

| Field | Purpose |
|---|---|
| `id` | Unique RFQ identifier |
| `component_id` | Component being requested |
| `supplier_id` | Supplier company assigned to the RFQ |
| `quantity_requested` | Quantity needed |
| `status` | Current RFQ lifecycle state |
| `quoted_price` | Price submitted by supplier |
| `auto_triggered` | Whether RFQ was created automatically |
| `decision_note` | Owner note when accepting/rejecting |
| `created_at` | RFQ creation date |

---

## 🔗 Relationships

### Component

```txt
rfq_requests.component_id → components.id

Each RFQ is linked to one component.

Supplier
rfq_requests.supplier_id → suppliers.id

Each RFQ may be assigned to one supplier.

supplier_id can be NULL when no preferred supplier exists.

Supplier User Mapping

Supplier users are stored in users.

users.supplier_company_id → suppliers.id

So supplier authorization checks compare:

rfq_requests.supplier_id
=
users.supplier_company_id

Not:

rfq_requests.supplier_id
=
users.id
🔄 Status Values
draft
open
quoted
accepted
rejected
expired

Lifecycle:

draft → open → quoted → accepted / rejected / expired
🤖 Auto RFQ Flag
auto_triggered = 0 → manually created RFQ
auto_triggered = 1 → automatically created from low stock
💰 Quote Data

Before supplier response:

quoted_price = NULL

After supplier submits quote:

quoted_price = supplier price
status = quoted
🧠 Important Design Note

RFQs are internal procurement records.

They are not connected to clients/customers.

owner → supplier

not:

client → owner
✅ Summary
rfq_requests stores procurement requests
components define what is being requested
suppliers define who receives the RFQ
users.supplier_company_id links supplier employees to supplier companies
status controls the RFQ lifecycle