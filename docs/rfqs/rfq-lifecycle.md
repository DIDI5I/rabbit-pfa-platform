# RFQ Lifecycle

## 🎯 Overview

The RFQ system follows a **strict state machine** to ensure valid transitions and maintain data integrity.

Each RFQ moves through a predefined set of statuses from creation to completion.

---

## 🔄 Lifecycle States

```txt
draft → open → quoted → accepted / rejected / expired
📌 State Definitions
🟡 draft
Initial state
RFQ is created but not yet sent to supplier
Can be:
manually created
auto-generated (low stock)

Allowed actions:

open
🔵 open
RFQ is visible to supplier
Waiting for supplier response (quote)

Allowed actions:

expire
quote (supplier)
🟣 quoted
Supplier has submitted a price
RFQ now contains quoted_price

Allowed actions:

accept (owner)
reject (owner)
expire
🟢 accepted
Owner accepts supplier quote
Procurement is confirmed
(Next step: stock update / inventory)

Allowed actions:

none
🔴 rejected
Owner rejects supplier quote

Allowed actions:

none
⚫ expired
RFQ is no longer valid
Can happen due to:
timeout
manual expiration

Allowed actions:

none
🚦 Transition Rules

Only specific transitions are allowed:

draft   → open
open    → quoted
open    → expired
quoted  → accepted
quoted  → rejected
quoted  → expired
❌ Invalid Transitions

The system prevents:

- quoting a draft RFQ
- accepting without a quote
- reopening a closed RFQ
- modifying finalized RFQs
⚙️ Enforcement

All transitions are enforced in:

RfqService

and validated using:

GET /rfqs/{id}/actions

which returns allowed actions dynamically.

🧠 Design Rationale

Using a state machine ensures:

- predictable behavior
- data consistency
- clear business logic
- easier debugging and testing
✅ Summary
- RFQs follow a strict lifecycle
- Only valid transitions are allowed
- Supplier interaction occurs only in "open"
- Final states are immutable