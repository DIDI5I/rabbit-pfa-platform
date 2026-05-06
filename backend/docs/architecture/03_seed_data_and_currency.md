# Rabbit — Seed Data and Currency Notes

## Dataset Purpose

A realistic industrial MRO dataset was generated and adapted for Rabbit.

It includes:

- suppliers
- components/products
- part_sources
- product relationships stored in `dependencies`

The dataset follows Rabbit's independent stock logic.

---

## Main Tables Seeded

```text
suppliers
components
part_sources
dependencies
```

Not fully seeded yet:

```text
users
orders
order_items
rfq_requests
rfq_revisions
stock_movements
```

Those require their schemas before generating full SQL.

---

## Product Examples

Assemblies:

```text
RAB-ASM-PMP-XR200
RAB-ASM-PMP-XR300
RAB-ASM-HYD-HPU80
RAB-ASM-CONV-DRV750
RAB-ASM-FAN-BD1450
```

Sub-assemblies:

```text
RAB-SUB-PMP-WET50
RAB-SUB-MTR-PKG75
RAB-SUB-HYD-PUMP11
RAB-SUB-BRG-UCP207-PAIR
RAB-SUB-MNT-KIT-PMP
```

Components/raw materials:

```text
RAB-CMP-BRG-6205
RAB-CMP-SEAL-MECH25
RAB-CMP-MTR-75-IE3
RAB-CMP-HOSE-KIT20
RAB-RAW-STL-C45-35
RAB-RAW-GSK-EPDM3
```

---

## MySQL Safe Update Issue

MySQL Workbench safe update mode blocked some delete statements.

Temporary solution:

```sql
SET SQL_SAFE_UPDATES = 0;

DELETE FROM part_sources
WHERE component_id IN (
    SELECT id
    FROM components
    WHERE sku LIKE 'RAB-%'
);

DELETE FROM dependencies
WHERE parent_id IN (
    SELECT id
    FROM components
    WHERE sku LIKE 'RAB-%'
);

SET SQL_SAFE_UPDATES = 1;
```

---

## Currency Decision

Initial generated prices were in EUR.

Rabbit's final project decision:

```text
All monetary values are stored in MAD by default.
```

Meaning:

```text
part_sources.unit_cost = MAD
rfq quoted prices = MAD
order item prices = MAD
selling prices = MAD
```

Conversion used during seed correction:

```text
1 EUR ≈ 11 MAD
```

Quick conversion SQL:

```sql
SET SQL_SAFE_UPDATES = 0;

UPDATE part_sources ps
JOIN components c ON c.id = ps.component_id
SET ps.unit_cost = ROUND(ps.unit_cost * 11, 2)
WHERE c.sku LIKE 'RAB-%';

SET SQL_SAFE_UPDATES = 1;
```
