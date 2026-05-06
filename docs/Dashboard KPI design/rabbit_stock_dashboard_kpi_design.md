# Rabbit — Stock Dashboard & KPI Design

## 1. Goal

The goal of the stock dashboard is to give the owner a clear view of stock health, product performance, and strategic stock priorities.

The dashboard is organized into three analytical lenses:

```text
1. General stock health
2. Product stock performance
3. ABC class performance
```

This structure lets the user move from a global diagnosis to product-level analysis, then to strategic prioritization.

---

## 2. Course-Based KPI Direction

The stock-management course emphasizes that managing stock means tracking stock entries and exits so the company can know the available quantity of each article at any moment.

The course also introduces indicators such as:

```text
stock d'alerte
rotation du stock
temps d'écoulement
couverture de stock
durée moyenne de stockage
taux de service
disponibilité à la livraison
```

Rabbit will use the indicators that fit the current backend and project scope.

---

## 3. Dashboard Lens 1 — General Stock Health

### Purpose

This view answers:

```text
What is the global state of the inventory?
```

### KPIs

```text
total_products
active_products
total_inventory_value
low_stock_count
out_of_stock_count
stock_ok_count
stock_in_movements
stock_out_movements
draft_purchase_lots
finalized_purchase_lots
```

### Why these KPIs?

They are directly supported by the current backend:

```text
components
stock_movements
purchase_lots
inventory
```

They help the owner quickly understand:

```text
How much stock value exists?
How many products are active?
How many products are low stock?
How many products are out of stock?
How many purchase lots still need finalization?
How much stock movement activity exists?
```

### Endpoint

```http
GET /dashboard/stock/general
```

---

## 4. Dashboard Lens 2 — Product Stock Performance

### Purpose

This view answers:

```text
How is each product performing individually?
```

### KPIs

```text
current_stock
stock_status
stock_value
stock_in_quantity
stock_out_quantity
movement_count
last_movement_at
latest_unit_purchase_cost
stock_rotation_rate
stock_coverage_days
average_storage_duration_days
stock_flow_time_days
```

### Why these KPIs?

They show product-level behavior:

```text
current_stock shows the actual stock level.
stock_status shows whether the product is OK, low stock, or out of stock.
stock_value shows the amount of money immobilized in the product.
stock_in_quantity and stock_out_quantity show movement activity.
last_movement_at shows whether the product is active or inactive.
rotation and duration indicators show whether the stock is moving efficiently.
```

### Calculation logic

```text
stock_value = current_stock × latest_unit_purchase_cost
```

```text
stock_rotation_rate = stock_out_quantity / average_stock
```

```text
average_daily_consumption = stock_out_quantity / period_days
```

```text
stock_coverage_days = current_stock / average_daily_consumption
```

```text
average_storage_duration_days = period_days / stock_rotation_rate
```

```text
stock_flow_time_days = average_storage_duration_days
```

### Important note

These historical indicators should be presented as estimated indicators if the data is limited.

Use metadata:

```json
{
  "period_days": 360,
  "calculation_mode": "estimated_from_stock_movements"
}
```

### Endpoint

```http
GET /dashboard/stock/products-performance?period_days=360
```

---

## 5. Dashboard Lens 3 — ABC Class Performance

### Purpose

This view answers:

```text
Which stock classes are strategically important?
```

ABC classification separates products into:

```text
Class A = high-value / critical products
Class B = medium-value products
Class C = low-value products
```

### ABC basis for Rabbit

Rabbit will classify products using stock value:

```text
stock_value = current_stock × latest_unit_purchase_cost
```

### ABC calculation

```text
1. Calculate stock_value for every product.
2. Sort products by stock_value descending.
3. Calculate each product's value_share_percent.
4. Calculate cumulative_value_percent.
5. Assign ABC class:
   - A = products contributing to roughly the first 80% of value
   - B = next 15%
   - C = last 5%
```

### Product-level ABC fields

```text
abc_class
stock_value
value_share_percent
cumulative_value_percent
current_stock
unit_purchase_cost
stock_status
```

### Class-level ABC KPIs

```text
product_count_by_class
inventory_value_by_class
value_share_percent_by_class
low_stock_count_by_class
out_of_stock_count_by_class
avg_current_stock_by_class
min_current_stock_by_class
max_current_stock_by_class
avg_rotation_rate_by_class
avg_coverage_days_by_class
avg_storage_duration_days_by_class
```

### Gini coefficient

Rabbit will also track:

```text
gini_coefficient
gini_label
```

The Gini coefficient measures concentration of stock value across products.

Interpretation:

```text
Gini close to 0 = stock value is evenly distributed
Gini close to 1 = stock value is concentrated in a few products
```

This complements ABC classification.

Example labels:

```text
gini < 0.3  → Faible concentration de valeur
gini < 0.6  → Concentration modérée de valeur
gini >= 0.6 → Forte concentration de valeur
```

### Endpoint

```http
GET /dashboard/stock/abc
```

---

## 6. Frontend Display Idea

The ABC dashboard should be one page with three sections:

```text
Classe A
Classe B
Classe C
```

Each section should show generic class KPIs:

```text
Valeur du stock
Part de valeur
Nombre d'articles
Stock moyen
Stock minimum
Stock maximum
Articles en stock faible
Articles en rupture
```

When the user clicks a class, it expands into a product-level dashboard/table.

This means:

```text
Calculate ABC globally.
Display products grouped by class.
```

The frontend should not calculate ABC itself. The backend should return a grouped response.

---

## 7. KPIs Included Now

Rabbit will implement these now:

```text
General stock value
Current stock
Stock status
Low-stock count
Out-of-stock count
Stock movement counts
Stock IN/OUT quantities
Purchase lot status counts
Product stock value
Stock rotation estimate
Stock coverage estimate
Average storage duration estimate
Stock flow time estimate
ABC classification
Gini coefficient
ABC class summaries
```

---

## 8. KPIs Left Out For Now

Rabbit will not implement these immediately:

```text
true service rate
delivery availability
quality indicators
productivity
cashflow / trésorerie
true historical forecasting
```

### Why?

They require data Rabbit does not properly model yet:

```text
partial deliveries
quantities delivered immediately
customer due dates
delivery delays
defective products
cashflow records
long-term real demand history
```

They can be added later if the project scope expands.

---

## 9. Backend Implementation Plan

Create the dashboard stock module:

```text
app/Queries/DashboardStockQuery.php
app/Repositories/DashboardStockRepository.php
app/Services/DashboardStockService.php
app/Controllers/DashboardStockController.php
```

Add routes:

```php
$router->getRoute('/dashboard/stock/general', [DashboardStockController::class, 'general'])
       ->only([AuthMiddleware::class]);

$router->getRoute('/dashboard/stock/products-performance', [DashboardStockController::class, 'productsPerformance'])
       ->only([AuthMiddleware::class]);

$router->getRoute('/dashboard/stock/abc', [DashboardStockController::class, 'abc'])
       ->only([AuthMiddleware::class]);
```

---

## 10. Immediate Next Step

Start with:

```text
DashboardStockQuery.php
```

The first backend endpoint should be:

```http
GET /dashboard/stock/general
```

Reason:

```text
It is the simplest endpoint.
It uses existing reliable data.
It gives immediate value to the frontend.
It establishes the dashboard module structure.
```

After that:

```text
GET /dashboard/stock/products-performance
```

Then:

```text
GET /dashboard/stock/abc
```

---

## 11. Future Backend Tasks

After the dashboard module:

```text
1. Finish full RFQ → draft purchase lot → finalize → stock IN test.
2. Role/authorization hardening.
3. Search service.
4. Notification queue.
5. Optional recommendation aggregation endpoint.
6. Documentation cleanup.
7. Optional future indicators: service rate, delivery availability, forecasting.
```

---

## 12. Final Design Summary

Rabbit's stock dashboard is not just a list of random cards.

It is structured as:

```text
Global stock health
→ Product stock performance
→ ABC strategic stock analysis
```

This makes the dashboard academically coherent and technically realistic for the current backend.
