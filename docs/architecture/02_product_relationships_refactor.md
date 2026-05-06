# Rabbit — Product Relationships Refactor

## Status

Completed.

The previous concept was:

```text
Product Dependencies / BOM
```

It has now been corrected to:

```text
Product Relationships
```

The database table is still named:

```text
dependencies
```

But its business meaning is no longer pure manufacturing BOM.

---

## Final `dependencies` Table Meaning

```text
dependencies
├── id
├── parent_id
├── child_id
├── relation_type
├── qty_required
├── uom
├── is_phantom
├── notes
├── created_at
└── updated_at
```

Allowed `relation_type` values:

```text
internal_structure
replacement_part
compatible_part
compatible_alternative
spare_part
accessory
related_product
```

---

## Schema Migration Done

```sql
ALTER TABLE dependencies
ADD COLUMN relation_type ENUM(
  'internal_structure',
  'replacement_part',
  'compatible_part',
  'compatible_alternative',
  'spare_part',
  'accessory',
  'related_product'
) NOT NULL DEFAULT 'internal_structure'
AFTER child_id;
```

Optional timestamp:

```sql
ALTER TABLE dependencies
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
```

---

## Unique Constraint Updated

Old rule:

```text
parent_id + child_id
```

New rule:

```text
parent_id + child_id + relation_type
```

SQL:

```sql
ALTER TABLE dependencies
DROP INDEX uq_dependency;

ALTER TABLE dependencies
ADD UNIQUE KEY uq_dependency_relation (
  parent_id,
  child_id,
  relation_type
);
```

This allows:

```text
Pump XR200 → Bearing 6205 as internal_structure
Pump XR200 → Bearing 6205 as replacement_part
```

But blocks duplicate same-type relationships.

---

## Backend Files Updated

```text
StoreDependencyRequest
UpdateDependencyRequest
DependencyQuery
DependencyRepository
DependencyService
DependencyController
```

---

## API Contract

Routes can remain:

```text
GET    /products/{id}/dependencies
POST   /products/{id}/dependencies
PATCH  /products/{id}/dependencies/{dependencyId}
DELETE /products/{id}/dependencies/{dependencyId}
```

Important:

```text
{dependencyId} = dependencies.id
```

It is no longer `child_id`.

---

## POST Payload

```json
{
  "child_id": 6,
  "relation_type": "replacement_part",
  "qty_required": 2,
  "uom": "pcs",
  "is_phantom": false,
  "notes": "Recommended replacement bearing for Pump XR200."
}
```

---

## Validation Rules

```text
child_id is required
qty_required is required
qty_required > 0
relation_type must be valid
parent and child must exist
no self relationship
no circular relationship
no duplicate same parent + child + relation_type
```

---

## Final Rule

```text
dependencies/product_relationships = why products are connected
stock_movements = what physically happened to inventory
```
