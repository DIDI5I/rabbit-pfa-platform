# Pending Purchase Lot Cost Updates

Stage 11D.5 added a cleaner interaction:

```text
User: finalize purchase lot 45
Rabbit: pending action with costs = 0

User: add transport cost 100 and customs cost 50
Rabbit: updates the pending action and returns updated preview

User: add other cost 5
Rabbit: updates the pending action again

User: confirm
Rabbit: finalizes the purchase lot using the latest pending costs
```

Safety rule:

```text
Cost updates only work if the current pending action is finalize_purchase_lot.
```
