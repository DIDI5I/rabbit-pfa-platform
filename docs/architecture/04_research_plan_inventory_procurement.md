# Rabbit — Research Plan for Inventory and Procurement

## Main Research Resources

Uploaded course resources:

```text
2Introduction&GS.pdf
3Coût (1).pdf
4IndicateurStock.pdf
4Méthode de prévision.pdf
6Méthode deGS 1.pdf
```

They support Rabbit's inventory, procurement, KPIs, valuation, replenishment, and forecasting layers.

---

## Research Area 1 — Stock Movement Fundamentals

Focus:

```text
stock = initial stock + entries - exits
stock réel vs stock informatique
stock entry / stock exit transactions
inventaire permanent
inventaire périodique
```

Rabbit implementation:

```text
stock_movements
current_stock calculation
stock history endpoint
```

---

## Research Area 2 — Inventory Levels

Focus:

```text
stock minimum
stock maximum
stock moyen
stock de sécurité
stock d’alerte
délai d’obtention
délai de sécurité
consommation journalière
```

Preferred Rabbit naming:

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

---

## Research Area 3 — Replenishment Methods

Focus:

```text
quand commander ?
combien commander ?
point de commande
réapprovisionnement périodique
date fixe / quantité fixe
date fixe / quantité variable
date variable / quantité fixe
date variable / quantité variable
```

Rabbit implementation:

```text
low stock alerts
RFQ candidates
recommended reorder quantity
```

---

## Research Area 4 — Stock Valuation and Cost

Focus:

```text
coût d’achat
coût de passation
coût de possession
coût de rupture
CMUP fin de période
CMUP après chaque entrée
FIFO
LIFO
```

Rabbit decision:

```text
Use CMUP après chaque entrée for operational inventory valuation later.
```

V1:

```text
estimated_stock_value = current_stock × preferred_supplier_unit_cost
```

---

## Research Area 5 — ABC Classification

Rabbit policy:

### Class A

```text
continuous monitoring
safety stock required
stock alert
automatic RFQ draft candidate
strict replenishment
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
```

---

## Postponed

```text
forecasting
advanced KPIs
full CMUP
FIFO/LIFO
ABC automation
```
