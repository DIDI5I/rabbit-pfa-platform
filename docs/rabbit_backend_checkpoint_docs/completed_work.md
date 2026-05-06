# Completed Work — Rabbit Backend Checkpoint

## 1. Route and Middleware Hardening

The route file was reviewed and hardened while preserving the existing route style.

The preferred existing style was kept:

```php
->only([OwnerMiddleware::class]);
```

Instead of converting routes to:

```php
->only([AuthMiddleware::class, OwnerMiddleware::class]);
```

This assumes `OwnerMiddleware` already handles authentication and owner-role validation.

## 2. Sensitive Route Protection

Sensitive/internal endpoints were hardened so they are no longer accessible as normal authenticated or public routes.

Owner/internal route groups include:

```text
/products/*
/promotions/*
/reviews/*
/stock/*
/inventory/*
/purchase-lots/*
/dashboard/*
/stock/intelligence/*
/cost-rollup
```

Client-safe public/catalogue routes remain under:

```text
/catalog/*
```

## 3. RFQ Route Order Fix

The RFQ workflow routes were moved before the dynamic route:

```php
/rfqs/{id}
```

This prevents static workflow routes such as:

```text
/rfqs/open
/rfqs/accept
/rfqs/reject
/rfqs/expire
```

from being interpreted as:

```text
/rfqs/{id}
```

where `id = open`, `accept`, `reject`, or `expire`.

## 4. Stock Intelligence Route Style Fix

The stock intelligence route was aligned with the current file style.

Correct style:

```php
$router->getRoute('/stock/intelligence/reorder-recommendations', [StockIntelligenceController::class, 'reorderRecommendations'])
       ->only([OwnerMiddleware::class]);
```

This endpoint is owner-only because it exposes reorder recommendations, preferred supplier data, purchase costs, reorder value, demand model selection, and stock risk information.

## 5. Session-Based Auth Confirmed

Login works using PHP session cookies.

The login response does not currently return a bearer token. Instead, it sets a PHP session cookie:

```text
PHPSESSID=...
```

Testing with curl requires saving and reusing cookies:

```cmd
curl.exe -i -c cookies.txt -X POST http://localhost:8888/login -H "Content-Type: application/json" -d "{\"email\":\"owner@rabbit.ma\",\"password\":\"test1234\"}"
```

Then:

```cmd
curl.exe -i -b cookies.txt http://localhost:8888/products
```

## 6. Inventory Duplicate Row Fix

The `/inventory` endpoint previously returned duplicate rows when a component had multiple preferred suppliers/sources.

The query was fixed to return one row per component by selecting a single source using this rule:

```text
preferred source first
then highest/newest id as tie-breaker
```

Stock calculation was also moved into a subquery to prevent stock quantities from being multiplied by supplier joins.

## 7. Inventory Alerts Verified

The `/inventory/alerts` endpoint works after the inventory query fix.

It returns only products where:

```text
current_stock <= low_stock_threshold
```

No duplicate alert rows were observed after the fix.
