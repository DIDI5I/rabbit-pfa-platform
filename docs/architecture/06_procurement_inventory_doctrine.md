# Rabbit — Procurement and Inventory Doctrine

## Locked Direction

Rabbit's procurement/inventory system will evolve toward:

```text
lot-level purchase cost capture
movement-based inventory
CMUP valuation
ABC classification
stock level policies by class
RFQ trigger / reorder point logic
selling price from cost + margin
```

---

## Purchased Lot Cost

For every lot bought, Rabbit should eventually store:

```text
product_id
supplier_id
quantity_received
buying_unit_price
purchase_date
reference_type
reference_id
```

Cost drivers:

```text
transport_cost
customs_cost
handling_cost
packaging_cost
order_preparation_cost
other_cost
```

Formula:

```text
coût d’achat =
prix d’achat fournisseur
+ transport
+ douane
+ transit
+ emballage
+ préparation commande
+ autres frais d’approvisionnement
```

---

## Inventory Valuation

Preferred future method:

```text
CMUP après chaque entrée
```

Formula:

```text
new_cmup =
(existing_stock_value + new_entry_value)
/
(existing_quantity + new_entry_quantity)
```

V1 temporary valuation:

```text
estimated_stock_value = current_stock × preferred_supplier_unit_cost
```

---

## Stock Levels

Preferred naming:

```text
lead_time_stock = average_daily_demand × supplier_lead_time_days
safety_stock = average_daily_demand × safety_delay_days
reorder_point = lead_time_stock + safety_stock
stock_alert = reorder_point
```

Expanded:

```text
stock_alert = average_daily_demand × (supplier_lead_time_days + safety_delay_days)
```

For now, use `low_stock_threshold` for V1.

---

## ABC Classification Policy

### Class A

```text
continuous monitoring
safety stock required
stock alert
automatic RFQ draft candidate
strict supplier lead-time tracking
```

### Class B

```text
regular monitoring
stock alert
RFQ recommendation
owner confirms action
```

### Class C

```text
periodic review
no safety stock
no automatic RFQ
grouped purchase recommendation
trigger only when at minimum or during review
```

---

## RFQ Trigger Logic

Future logic:

```text
if class A and current_stock <= stock_alert:
    create RFQ draft candidate

if class B and current_stock <= stock_alert:
    recommend RFQ

if class C and current_stock <= stock_minimum:
    add to periodic replenishment list
```

V1 logic:

```text
if current_stock <= low_stock_threshold:
    show low-stock alert
```

---

## Selling Price Logic

Future basic selling formula:

```text
selling_price = CMUP_unit_cost × (1 + profit_margin_percent)
```

---

## Separation of Concerns

Do not overload `stock_movements`.

Recommended future split:

```text
stock_movements
= physical stock IN/OUT history

purchase_lots
= purchase cost details and cost drivers

inventory_valuation
= CMUP/FIFO valuation snapshots if needed
```
