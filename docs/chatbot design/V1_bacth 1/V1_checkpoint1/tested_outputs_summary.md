# Tested Outputs Summary

## Catalogue Search

Input:

```text
what products are available?
```

Output confirmed:

```text
answer: I found 34 catalogue product(s). Showing the first 5.
summary.total = 34
summary.shown = 5
items_preview length = 5
result_meta.has_more = true
```

## Active Promotions

Input:

```text
is there anything on promotion?
```

Output confirmed:

```text
answer: There are 1 active promotion(s).
summary.total = 1
items_preview length = 1
result_meta.has_more = false
```

Small cleanup recommended:

```text
filters should be an object/map, not [].
```

## Inventory Alerts

Input:

```text
which items are low?
```

Output confirmed:

```text
intent = inventory_alerts
role = owner
answer = There are 5 inventory alert(s): 2 out of stock and 3 low stock.
items_preview length = 5
```

## Cost Rollup

Input:

```text
how much does product 1 cost us?
```

Output confirmed:

```text
intent = cost_rollup
role = owner
summary.product_id = 1
summary.total_material_cost_mad = 157040.6
summary.unique_components = 14
items_preview = []
```

## Reorder Recommendations

Input:

```text
what needs restocking?
```

Output confirmed:

```text
intent = reorder_recommendations
role = owner
recommended_count = 3
critical_count = 2
high_count = 1
items_preview length = 3
```
