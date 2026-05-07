# Purchase Lot Finalization

Intent:

```text
finalize_purchase_lot
```

Example:

```text
finalize purchase lot 45
```

This creates a pending action with extra costs defaulting to 0.

Preview shows:

```text
purchase_lot_id
current_status
new_status = finalized
component
supplier
quantity_received
supplier_unit_price
supplier_total_price
extra_costs
estimated_total_purchase_cost
estimated_unit_purchase_cost
stock_impact
side_effects
```

Stock impact:

```text
type = stock_in
reason = PURCHASE_RECEIVED
quantity = purchase lot quantity
```

Risk level:

```text
high
```
