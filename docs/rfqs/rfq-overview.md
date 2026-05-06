# RFQ System Overview
# RFQ System

## Purpose
Internal procurement system:
owner → suppliers

Clients are NOT involved.

## Lifecycle

draft → open → quoted → accepted / rejected / expired

## Features implemented

- manual RFQ creation
- auto RFQ (low stock)
- supplier quote
- state transitions
- role-based access
## 🎯 Purpose

The RFQ (Request for Quotation) system is designed to handle **internal procurement** within the platform.

It allows the company (owner) to request price quotations from suppliers when stock is needed.

---

## 🧠 Core Concept

RFQs in this system are **not customer-facing**.

They are used exclusively for:

```txt
owner (company) → suppliers

This means:

Clients (customers) do not interact with RFQs
RFQs are part of the supply chain, not the sales process
🔄 Procurement Flow

The RFQ system follows this workflow:

Stock ↓ or manual request
→ RFQ created (draft)
→ RFQ opened
→ Supplier submits quote
→ RFQ becomes quoted
→ Owner accepts or rejects
→ (next: stock replenishment)
⚙️ How RFQs Are Triggered

RFQs can be created in two ways:

1. Manual Creation

The owner explicitly creates an RFQ:

POST /rfqs

Used when:

New components are needed
Planned procurement
Special sourcing requests
2. Automatic Creation (Low Stock Trigger)

RFQs are automatically generated when:

stock_qty <= low_stock_threshold

Behavior:

RFQ is created in draft
Preferred supplier is selected if available
Duplicate active RFQs are prevented
🧩 System Scope

The RFQ system is part of a larger architecture:

Procurement (RFQ) + Inventory + E-commerce

Where:

RFQ → handles supplier sourcing
Inventory → manages stock levels
E-commerce → handles customer purchases
🚫 Out of Scope

This system does not support:

Customer-driven RFQs
Custom manufacturing requests
B2B quote negotiation with clients

These would require a separate system design.

✅ Summary
- Internal procurement system
- Owner → Supplier interaction
- Not exposed to clients
- Supports manual and automatic RFQ creation