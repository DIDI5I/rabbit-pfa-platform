# Role-Based Sanitization

## Owner related product preview

```text
id
name
sku
category
stock_status
stock_qty
low_stock_threshold
relation_type
```

## Guest/client/supplier related product preview

```text
name
sku
category
availability_status
relation_type
```

Public-facing roles do not receive:

```text
id
stock_qty
low_stock_threshold
matched_source
supplier_id
unit_cost
is_active
dependency_id
parent_id
child_id
qty_required
is_phantom
same_supplier group
owner stock intelligence
reorder section
cost/procurement data
```
