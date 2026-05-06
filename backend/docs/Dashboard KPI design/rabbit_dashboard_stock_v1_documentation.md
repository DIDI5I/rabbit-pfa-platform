# Rabbit — Dashboard Stock V1 Documentation

## 1. Goal

The Dashboard Stock module gives the owner visual stock analysis through dashboard-ready API endpoints.

The module is built around three analytical levels:

```text
1. General stock health
2. Product-specific stock dashboard
3. ABC stock analysis
```

The backend always follows the Rabbit API response format:

```json
{
  "message": "...",
  "data": {}
}
```

---

## 2. Implemented Endpoints

```http
GET /dashboard/stock/general
GET /dashboard/stock/products-performance
GET /dashboard/stock/products/{productId}/performance
GET /dashboard/stock/abc
GET /dashboard/stock/abc/{class}
```

---

# 3. General Stock Dashboard

## Endpoint

```http
GET /dashboard/stock/general
```

## Purpose

This endpoint gives a global stock-health overview.

It answers:

```text
What is the current global state of the inventory?
```

## Returned KPIs

```text
total_inventory_value
currency
total_products
active_products
out_of_stock_count
low_stock_count
stock_ok_count
total_stock_movements
stock_in_movements
stock_out_movements
stock_movements_this_month
stock_in_movements_this_month
stock_out_movements_this_month
total_purchase_lots
draft_purchase_lots
finalized_purchase_lots
cancelled_purchase_lots
stock_health_summary
```

## Stock Health Summary Logic

```text
CRITICAL = at least one product is out of stock
WARNING  = no out-of-stock products, but at least one low-stock product
HEALTHY  = no low-stock and no out-of-stock products
```

---

# 4. Products Performance Dataset

## Endpoint

```http
GET /dashboard/stock/products-performance
```

Optional:

```http
GET /dashboard/stock/products-performance?period_days=90
```

## Purpose

This endpoint returns product-level performance data for all active products.

It is useful as a backend/reporting dataset. It is not the main visual product dashboard.

## Returned Data

For each product:

```text
id
name
sku
category
current_stock
opening_stock
average_stock
low_stock_threshold
stock_status
latest_unit_purchase_cost
stock_value
stock_in_quantity
stock_out_quantity
movement_count
last_movement_at
stock_rotation_rate
average_daily_consumption
stock_coverage_days
average_storage_duration_days
stock_flow_time_days
```

## Calculation Mode

```text
estimated_from_stock_movements
```

This means the historical indicators are estimated from available stock movements, not from a long real historical dataset.

---

# 5. Product-Specific Stock Dashboard

## Endpoint

```http
GET /dashboard/stock/products/{productId}/performance
```

Optional:

```http
GET /dashboard/stock/products/{productId}/performance?period_days=90
```

## Purpose

This endpoint supports a dedicated visual dashboard for one product.

It returns:

```text
product identity
KPI cards
chart-ready data
recent activity
```

## Response Shape

```json
{
  "message": "Product stock dashboard fetched successfully",
  "data": {
    "period_days": 360,
    "calculation_mode": "estimated_from_stock_movements",
    "product": {},
    "kpis": {},
    "charts": {},
    "recent_activity": {}
  }
}
```

## Product Dashboard Chart Blocks

```text
stock_movements_by_type
stock_movement_timeline
cost_history
```

Frontend can use these for:

```text
IN vs OUT chart
movement timeline chart
cost history chart
KPI cards
```

---

# 6. KPI Formulas

## Current Stock

```text
current_stock = total IN movements - total OUT movements
```

## Opening Stock Estimate

```text
opening_stock = current_stock - stock_in_quantity + stock_out_quantity
```

## Average Stock Estimate

```text
average_stock = (opening_stock + current_stock) / 2
```

## Stock Value

```text
stock_value = current_stock × latest_unit_purchase_cost
```

Cost priority:

```text
1. latest finalized purchase_lot.unit_purchase_cost
2. preferred part_sources.unit_cost
3. 0 if no cost exists
```

## Stock Rotation Rate

```text
stock_rotation_rate = stock_out_quantity / average_stock
```

If average stock is zero, the value is null.

## Average Daily Consumption

```text
average_daily_consumption = stock_out_quantity / period_days
```

## Stock Coverage Days

```text
stock_coverage_days = current_stock / average_daily_consumption
```

If there is no consumption, the value is null.

## Average Storage Duration

```text
average_storage_duration_days = period_days / stock_rotation_rate
```

If rotation is zero or unavailable, the value is null.

## Stock Flow Time

```text
stock_flow_time_days = average_storage_duration_days
```

For this project version, stock flow time is treated as equivalent to average storage duration.

---

# 7. ABC Stock Dashboard

## Endpoint

```http
GET /dashboard/stock/abc
```

## Purpose

This endpoint gives global ABC analysis of stock value.

It answers:

```text
Where is inventory value concentrated?
Which products/classes deserve more attention?
```

## ABC Calculation

```text
1. Calculate stock_value for every active product.
2. Sort products by stock_value descending.
3. Calculate value_share_percent.
4. Calculate cumulative_value_percent.
5. Assign ABC class:
   - A = products contributing to approximately the first 80% of value
   - B = next 15%
   - C = last 5%
```

## Returned Structure

```json
{
  "message": "ABC stock dashboard fetched successfully",
  "data": {
    "global": {},
    "charts": {},
    "classes": {}
  }
}
```

## Global ABC KPIs

```text
total_products
total_inventory_value
currency
gini_coefficient
gini_label
a_value_share_percent
b_value_share_percent
c_value_share_percent
```

## Chart Blocks

```text
value_by_class
product_count_by_class
risk_by_class
```

## Class Blocks

Each class includes:

```text
class
label
description
product_count
inventory_value
value_share_percent
avg_current_stock
min_current_stock
max_current_stock
low_stock_count
out_of_stock_count
products
```

---

# 8. Gini Coefficient

## Purpose

The Gini coefficient measures how concentrated stock value is across products.

```text
Gini close to 0 = stock value is evenly distributed
Gini close to 1 = stock value is concentrated in a few products
```

## Labels

```text
gini < 0.3  → Faible concentration de valeur
gini < 0.6  → Concentration modérée de valeur
gini >= 0.6 → Forte concentration de valeur
```

Example:

```json
{
  "gini_coefficient": 0.6748,
  "gini_label": "Forte concentration de valeur"
}
```

---

# 9. ABC Class Dashboard

## Endpoint

```http
GET /dashboard/stock/abc/{class}
```

Examples:

```http
GET /dashboard/stock/abc/A
GET /dashboard/stock/abc/B
GET /dashboard/stock/abc/C
```

## Purpose

This endpoint supports a dedicated dashboard for one ABC class.

It returns:

```text
class identity
class KPIs
chart-ready data
products in the class
```

## Response Shape

```json
{
  "message": "ABC class dashboard fetched successfully",
  "data": {
    "class": {},
    "kpis": {},
    "charts": {},
    "products": []
  }
}
```

## Class KPIs

```text
product_count
inventory_value
value_share_percent
avg_current_stock
min_current_stock
max_current_stock
low_stock_count
out_of_stock_count
avg_rotation_rate
avg_coverage_days
avg_storage_duration_days
```

## Chart Blocks

```text
stock_status_distribution
top_products_by_value
stock_value_distribution
movement_distribution
```

---

# 10. Frontend Usage

## Main Stock Dashboard

Use:

```http
GET /dashboard/stock/general
```

For global cards.

## Product Visual Dashboard

Use:

```http
GET /dashboard/stock/products/{productId}/performance
```

For a dedicated product page with charts.

## Global ABC Dashboard

Use:

```http
GET /dashboard/stock/abc
```

For global ABC charts and class cards.

## ABC Class Dashboard

Use:

```http
GET /dashboard/stock/abc/{class}
```

For expanded dashboards per class.

---

# 11. Completed Files

```text
app/Queries/DashboardStockQuery.php
app/Repositories/DashboardStockRepository.php
app/Services/DashboardStockService.php
app/Controllers/DashboardStockController.php
routes file updates
```

---

# 12. Completed Routes

```php
$router->getRoute('/dashboard/stock/general', [DashboardStockController::class, 'general'])
       ->only([AuthMiddleware::class]);

$router->getRoute('/dashboard/stock/products/{productId}/performance', [DashboardStockController::class, 'productPerformance'])
       ->only([AuthMiddleware::class]);

$router->getRoute('/dashboard/stock/products-performance', [DashboardStockController::class, 'productsPerformance'])
       ->only([AuthMiddleware::class]);

$router->getRoute('/dashboard/stock/abc', [DashboardStockController::class, 'abc'])
       ->only([AuthMiddleware::class]);

$router->getRoute('/dashboard/stock/abc/{class}', [DashboardStockController::class, 'abcClass'])
       ->only([AuthMiddleware::class]);
```

---

# 13. Current Status

Dashboard Stock V1 is functionally complete.

Implemented:

```text
general stock dashboard
product performance dataset
product-specific visual dashboard
global ABC dashboard
ABC class dashboard
Gini coefficient
chart-ready response blocks
```

---

# 14. Notes and Limitations

Some indicators can return null when there is no stock consumption.

Example:

```text
stock_out_quantity = 0
→ average_daily_consumption = 0
→ stock_coverage_days = null
→ average_storage_duration_days = null
```

This is correct. It means the system does not have enough consumption data for that product/class.

Some products have:

```text
latest_unit_purchase_cost = 0
stock_value = 0
```

This means those products do not yet have finalized purchase lot cost or preferred supplier cost.

For better ABC accuracy, important products should have cost data.

---

# 15. Next Backend Stage

After Dashboard Stock V1, the next backend stage should be one of:

```text
1. Role/authorization hardening
2. Search service
3. Notification queue
4. Finish RFQ → draft purchase lot → finalize → stock IN testing
5. Recommendation aggregation endpoint
```

Recommended next stage:

```text
Role/authorization hardening
```

Reason:

```text
The backend now has many powerful endpoints.
Before adding more features, access control should be tightened.
```
