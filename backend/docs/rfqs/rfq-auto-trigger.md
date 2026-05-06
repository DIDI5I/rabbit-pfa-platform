# RFQ Auto Trigger (Low Stock)

# Auto RFQ

## Trigger

stock_qty <= low_stock_threshold

## Behavior

- creates draft RFQ
- assigns preferred supplier
- prevents duplicate active RFQs

## Called from

ProductService::update()

## 🎯 Overview

The RFQ system supports **automatic RFQ creation** when stock levels fall below a defined threshold.

This feature ensures that procurement is **proactive**, reducing the risk of stockouts.

---

## ⚙️ Trigger Condition

An automatic RFQ is created when:

```txt
stock_qty <= low_stock_threshold

This condition is evaluated when:

- product stock is updated
- inventory changes occur
🔄 Auto RFQ Flow
Stock update
→ Check threshold
→ If low → Create RFQ (draft)
→ Assign supplier (if available)
📌 Behavior

When triggered:

- RFQ is created in "draft" status
- auto_triggered = 1
- supplier_id is set if preferred supplier exists
- duplicate active RFQs are prevented
🧠 Supplier Selection

The system attempts to find a supplier using:

part_sources table

Logic:

component → preferred supplier → assign to RFQ

If no supplier is found:

supplier_id = NULL
🚫 Duplicate Prevention

Before creating a new RFQ, the system checks:

Does an active RFQ already exist?
(status IN: draft, open, quoted)

If yes:

→ Do not create another RFQ
🔧 Implementation Location

The logic is handled in:

RfqService::createAutoDraftForLowStock()

Called from:

ProductService::update()
📊 Example
Scenario
Component stock = 5
Threshold = 10
Result
→ RFQ created automatically
→ status = draft
→ auto_triggered = 1
🔐 Authorization

Auto RFQs are created under:

owner context

Even though triggered automatically, they follow the same lifecycle rules as manual RFQs.

🧩 System Role

This feature connects:

Inventory → Procurement

Making the system:

event-driven and proactive
⚠️ Limitations
- No batching of multiple components
- No scheduling (real-time only)
- No notifications yet
✅ Summary
- Automatically creates RFQs when stock is low
- Prevents duplicate active RFQs
- Uses preferred supplier if available
- Integrates inventory with procurement logic