# Auth System

## Type
Session-based (PHP)

## Session Keys
- user_id
- role
- supplier_company_id

## Roles
- owner
- fournisseur
- client

## Rules
- Auth::id() → users.id
- Auth::supplierCompanyId() → suppliers.id

IMPORTANT:
rfq_requests.supplier_id = suppliers.id
→ must compare with supplier_company_id