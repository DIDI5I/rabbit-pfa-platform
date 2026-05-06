# RFQ Authorization

## 🎯 Overview

The RFQ system enforces **role-based access control** to ensure that only authorized users can perform specific actions.

Authorization is handled at the **route level using middleware**, and reinforced at the **service level for business rules**.

---

## 👥 Roles

The system defines three roles:

```txt
owner
fournisseur
client
🔐 Role Permissions
🟢 Owner (Company)

The owner is responsible for managing procurement.

Allowed actions:

- Create RFQ
- Open RFQ
- Accept RFQ
- Reject RFQ
- Expire RFQ
- View all RFQs
- View RFQ details
🟣 Fournisseur (Supplier)

Suppliers can respond to RFQs assigned to them.

Allowed actions:

- View assigned RFQs
- Submit quotes

Restrictions:

- Cannot create RFQs
- Cannot modify RFQ state (except quoting)
- Can only quote RFQs assigned to their company
🔴 Client (Customer)

Clients are not part of the RFQ system.

- No access to RFQs
⚙️ Middleware Enforcement

Authorization is enforced using middleware at the route level:

->only([OwnerMiddleware::class])
->only([SupplierMiddleware::class])
->only([AuthMiddleware::class])
Middleware Mapping
OwnerMiddleware      → owner-only endpoints
SupplierMiddleware   → supplier-only endpoints
AuthMiddleware       → any authenticated user
🧠 Supplier Identity Resolution

Suppliers are identified via:

users.supplier_company_id → suppliers.id

Important:

rfq_requests.supplier_id → suppliers.id

Therefore, authorization checks use:

Auth::supplierCompanyId()

NOT:

Auth::id()
🚦 Business Rule Enforcement

Even after middleware, additional checks are performed in the service layer.

Example (supplier quoting):

- RFQ must be in "open" state
- supplier_id must match supplier_company_id

This ensures:

- No unauthorized quoting
- No invalid state transitions
🔒 Security Guarantees

The system ensures:

- Only owners manage procurement
- Only assigned suppliers can quote
- Clients cannot access RFQs
- Invalid actions are blocked at both route and service levels
⚠️ Common Pitfall
Using Auth::id() instead of supplier_company_id for supplier checks

This would allow incorrect authorization.

✅ Summary
- Role-based access enforced via middleware
- Supplier identity resolved via company ID
- Service layer enforces business rules
- RFQ system is fully secured against invalid access