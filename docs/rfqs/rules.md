# RFQ Rules

## Role Access

owner:
- full control

fournisseur:
- view assigned RFQs
- submit quotes

client:
- no access

## Supplier Identity

Use:
Auth::supplierCompanyId()

NOT:
Auth::id()

## State Enforcement

- cannot quote draft
- cannot accept without quote
- cannot modify final states