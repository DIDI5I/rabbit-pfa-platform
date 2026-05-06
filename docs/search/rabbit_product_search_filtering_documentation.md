# Rabbit — Product Search & Filtering Documentation

## 1. Goal

The goal of this stage was to decide whether Rabbit needs a separate search service or whether the existing `/products` endpoint can serve as the product search endpoint.

Final decision:

```text
Do not create a separate /search/products endpoint yet.
Use and improve the existing /products endpoint.
```

Reason:

```text
The existing /products endpoint already returns rich, paginated product data.
It already includes supplier information, stock status, category, SKU, and description.
For a school project, improving /products is simpler, cleaner, and sufficient.
```

---

## 2. Main Endpoint

```http
GET /products
```

The endpoint should support product discovery through query parameters:

```http
GET /products?q=pompe
GET /products?search=pompe
GET /products?category=component
GET /products?stock_status=low
GET /products?supplier_id=6
GET /products?supplier_name=Casatech
GET /products?supplier_country=Morocco
GET /products?min_supplier_rating=4
GET /products?min_price=100
GET /products?max_price=1000
GET /products?page=1&limit=10
```

These filters can be combined:

```http
GET /products?q=pompe&category=assembly&stock_status=ok&page=1&limit=10
```

---

## 3. Standard Response Format

Rabbit backend responses must always follow this format:

```json
{
  "message": "...",
  "data": {}
}
```

For `/products`, the response format is:

```json
{
  "message": "Products fetched successfully",
  "data": {
    "items": [],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 47,
      "total_pages": 5
    }
  }
}
```

---

## 4. Product Fields Returned

Each product item can include:

```text
id
name
sku
description
category
unit_of_measure
stock_qty
low_stock_threshold
is_active
created_at
stock_status
has_children
node_role
matched_source
```

`matched_source` contains preferred supplier information:

```json
{
  "supplier_id": 6,
  "supplier_name": "Casatech Hydraulique",
  "unit_cost": 2145
}
```

---

## 5. Important Stock Note

The `/products` endpoint may still use:

```text
stock_qty
```

for light catalog display and basic stock filtering.

But real stock intelligence comes from:

```http
GET /stock/{componentId}
GET /inventory
GET /dashboard/stock/general
GET /dashboard/stock/products/{productId}/performance
```

So the frontend should understand:

```text
/products = product catalog and discovery
/dashboard or /stock = serious stock analytics
```

---

## 6. ProductQuery.php

`ProductQuery::selectFields()` already returns rich product fields.

Important fields include:

```text
name
sku
description
category
unit_of_measure
stock_qty
low_stock_threshold
is_active
stock_status
has_children
node_role
matched_source
```

`matched_source` is built from preferred `part_sources` and `suppliers`.

This is useful because product search results can show supplier information without a separate supplier request.

---

## 7. ProductFilterQuery.php Changes

### Existing filters

The filter system already supported:

```text
active_only
search
category
low_stock
supplier_id
supplier_name
supplier_country
min_supplier_rating
min_price
max_price
```

### Improvement 1 — General search should include supplier name

Old search checked only:

```text
product name
SKU
description
```

Improved search checks:

```text
product name
SKU
description
supplier name
```

Recommended `search()` method:

```php
private function search(ProductRequest $request): self
{
    if ($request->search() !== null) {
        $this->where[] = "(
            c.name LIKE ?
            OR c.sku LIKE ?
            OR c.description LIKE ?
            OR EXISTS (
                SELECT 1
                FROM part_sources ps
                JOIN suppliers s ON s.id = ps.supplier_id
                WHERE ps.component_id = c.id
                  AND s.name LIKE ?
            )
        )";

        $search = '%' . $request->search() . '%';

        array_push($this->params, $search, $search, $search, $search);
    }

    return $this;
}
```

### Improvement 2 — Add `stock_status` filter

Add this to the filter chain:

```php
->stockStatus($request)
```

Recommended filter chain:

```php
$filter
    ->activeOnly($request)
    ->search($request)
    ->category($request)
    ->lowStock($request)
    ->stockStatus($request)
    ->supplierId($request)
    ->supplierName($request)
    ->supplierCountry($request)
    ->supplierRating($request)
    ->minPrice($request)
    ->maxPrice($request);
```

Add this method:

```php
private function stockStatus(ProductRequest $request): self
{
    if ($request->stockStatus() === null) {
        return $this;
    }

    if ($request->stockStatus() === 'out') {
        $this->where[] = "c.stock_qty <= 0";
    }

    if ($request->stockStatus() === 'low') {
        $this->where[] = "c.stock_qty > 0 AND c.stock_qty <= c.low_stock_threshold";
    }

    if ($request->stockStatus() === 'ok') {
        $this->where[] = "c.stock_qty > c.low_stock_threshold";
    }

    return $this;
}
```

### Minor cleanup

Change:

```php
->maxPrice($request);;
```

to:

```php
->maxPrice($request);
```

---

## 8. ProductRequest.php Changes

### Add allowed stock statuses

```php
private array $allowedStockStatuses = [
    'ok',
    'low',
    'out'
];
```

### Validate `stock_status`

Update validation:

```php
Validator::make($this->data)
    ->integer('page')
    ->integer('limit')
    ->in('category', $this->allowedCategories)
    ->in('stock_status', $this->allowedStockStatuses)
    ->numeric('min_price')
    ->numeric('max_price')
    ->numeric('min_supplier_rating')
    ->validate();
```

### Support both `search` and `q`

Replace `search()` with:

```php
public function search(): ?string
{
    $value = trim((string) ($this->data['search'] ?? $this->data['q'] ?? ''));

    return $value !== '' ? $value : null;
}
```

This allows:

```http
GET /products?search=pompe
GET /products?q=pompe
```

### Add `stockStatus()` getter

```php
public function stockStatus(): ?string
{
    $value = trim((string) ($this->data['stock_status'] ?? ''));

    return $value !== '' ? $value : null;
}
```

### Fix `activeOnly()`

Old:

```php
return $this->parseBool($this->data['active_only'] ?? null) ?? true; ;
```

Correct:

```php
public function activeOnly(): bool
{
    return $this->parseBool($this->data['active_only'] ?? null) ?? true;
}
```

---

## 9. Supported Query Parameters

| Parameter | Purpose | Example |
|---|---|---|
| `q` | General search alias | `/products?q=pompe` |
| `search` | General search | `/products?search=pompe` |
| `category` | Filter by category | `/products?category=component` |
| `stock_status` | Filter by stock status | `/products?stock_status=low` |
| `low_stock` | Legacy low-stock boolean filter | `/products?low_stock=true` |
| `supplier_id` | Filter by supplier id | `/products?supplier_id=6` |
| `supplier_name` | Filter by supplier name | `/products?supplier_name=Casatech` |
| `supplier_country` | Filter by supplier country | `/products?supplier_country=Morocco` |
| `min_supplier_rating` | Minimum supplier rating | `/products?min_supplier_rating=4` |
| `min_price` | Minimum unit cost | `/products?min_price=100` |
| `max_price` | Maximum unit cost | `/products?max_price=1000` |
| `page` | Pagination page | `/products?page=1` |
| `limit` | Pagination limit | `/products?limit=10` |
| `active_only` | Show only active products by default | `/products?active_only=false` |

---

## 10. Category Values

Supported categories:

```text
assembly
sub_assembly
component
raw_material
```

French labels for frontend:

```js
const PRODUCT_CATEGORY_LABELS = {
  assembly: "Assemblage",
  sub_assembly: "Sous-assemblage",
  component: "Composant",
  raw_material: "Matière première",
};
```

---

## 11. Stock Status Values

Backend values for `/products`:

```text
ok
low
out
```

Frontend labels:

```js
const STOCK_STATUS_LABELS = {
  ok: "Stock normal",
  low: "Stock faible",
  out: "Rupture de stock",
};
```

Note: dashboard endpoints may use uppercase statuses:

```text
OK
LOW_STOCK
OUT_OF_STOCK
```

The frontend should handle both depending on endpoint.

---

## 12. Test URLs

After implementing the changes, test:

```http
GET /products?q=pompe
```

Expected:

```text
Products where name, SKU, description, or supplier name contains "pompe".
```

```http
GET /products?q=Casatech
```

Expected:

```text
Products supplied by a supplier whose name contains "Casatech".
```

```http
GET /products?stock_status=low
```

Expected:

```text
Products where stock_qty > 0 and stock_qty <= low_stock_threshold.
```

```http
GET /products?stock_status=out
```

Expected:

```text
Products where stock_qty <= 0.
```

```http
GET /products?category=component&stock_status=ok
```

Expected:

```text
Active components with stock above threshold.
```

```http
GET /products?supplier_id=6
```

Expected:

```text
Products linked to supplier id 6.
```

---

## 13. Final Decision

For Rabbit V1:

```text
Use /products as the product search and filtering endpoint.
Do not create /search/products yet.
```

Reason:

```text
The existing product endpoint is already rich, paginated, and frontend-friendly.
Improving it is simpler and more coherent for the school project.
A separate search service can be added later only if needed.
```

---

## 14. Current Status

Product search/filtering design is complete.

Implemented or planned improvements:

```text
q alias for search
supplier-name search inside general search
stock_status filter
clean ProductRequest validation
same standard Rabbit response format
```

Next stage after this:

```text
Notification queue or recommendation aggregation endpoint.
```
