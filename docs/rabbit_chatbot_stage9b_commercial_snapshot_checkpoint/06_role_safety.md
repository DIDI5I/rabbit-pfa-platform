# Role Safety

Stage 9B preserves the Stage 7 role rules.

## Owner Output

Owner can still see:

```text
numeric related product IDs
stock_qty
low_stock_threshold
same_supplier section
owner stock intelligence
reorder priority/confidence/reason codes
```

## Guest/Public Output

Guest output remains public-safe.

Guest can see:

```text
product identity
availability
compatible alternatives
spare parts
same category
commercial section
rating distribution
promotion/review summary
```

Guest cannot see:

```text
numeric product IDs
stock_qty
low_stock_threshold
same_supplier section
owner stock intelligence
reorder section
cost
procurement
supplier-sensitive data
```

Commercial catalogue fields are considered public-safe.
