# Purchase Lot Finalization Test

Test flow:

```text
finalize purchase lot 45
add transport cost 100 and customs cost 50
add other cost 5
confirm
GET /purchase-lots/45
GET /stock/11
```

Final purchase lot values:

```text
purchase_lot_id = 45
status = finalized
transport_cost = 100
customs_cost = 50
other_cost = 5
total_purchase_cost = 1045
unit_purchase_cost = 52.25
reference_type = rfq
reference_id = 13
```

Final stock result:

```text
component #11 current_stock = 135
```
