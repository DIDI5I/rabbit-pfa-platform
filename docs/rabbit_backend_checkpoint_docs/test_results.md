# Test Results — Rabbit Backend Checkpoint

## Testing Environment

Backend base URL:

```text
http://localhost:8888
```

Operating system used during testing:

```text
Windows 10
```

Curl command used:

```text
curl.exe
```

Auth mode:

```text
PHP session cookie
```

## Login Test

Command:

```cmd
curl.exe -i -c cookies.txt -X POST http://localhost:8888/login -H "Content-Type: application/json" -d "{\"email\":\"owner@rabbit.ma\",\"password\":\"test1234\"}"
```

Result:

```http
HTTP/1.1 200 OK
```

Response contained:

```json
{
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin Rabbit",
      "email": "owner@rabbit.ma",
      "role": "owner",
      "company_name": "Rabbit MRO"
    }
  }
}
```

Status: Passed.

## Products Route

Without cookie:

```cmd
curl.exe -i http://localhost:8888/products
```

Result:

```json
{
  "error": "Validation failed",
  "fields": {
    "auth": ["User not authenticated."]
  }
}
```

With owner cookie:

```cmd
curl.exe -i -b cookies.txt http://localhost:8888/products
```

Result:

```http
HTTP/1.1 200 OK
```

Status: Passed.

## Public Catalogue Route

Command:

```cmd
curl.exe -i http://localhost:8888/catalog/products
```

Result:

```http
HTTP/1.1 200 OK
```

Status: Passed.

## Stock Intelligence Route

Without cookie:

```cmd
curl.exe -i http://localhost:8888/stock/intelligence/reorder-recommendations
```

Result:

```json
{
  "error": "Validation failed",
  "fields": {
    "auth": ["User not authenticated."]
  }
}
```

With owner cookie:

```cmd
curl.exe -i -b cookies.txt http://localhost:8888/stock/intelligence/reorder-recommendations
```

Result:

```http
HTTP/1.1 200 OK
```

Important observed summary:

```json
{
  "recommended_count": 3,
  "critical_count": 2,
  "high_count": 1,
  "estimated_reorder_value": 102850
}
```

Status: Passed.

## RFQ Route Order Test

Command:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/rfqs/open -H "Content-Type: application/json" -d "{}"
```

Result:

```json
{
  "error": "Validation failed",
  "fields": {
    "rfq_id": ["Missing required field: rfq_id"]
  }
}
```

Interpretation:

This confirms `/rfqs/open` reached the workflow controller method instead of being captured by `/rfqs/{id}`.

Status: Passed.

## Inventory Route

Without cookie:

```cmd
curl.exe -i http://localhost:8888/inventory
```

Result:

```json
{
  "error": "Validation failed",
  "fields": {
    "auth": ["User not authenticated."]
  }
}
```

With owner cookie:

```cmd
curl.exe -i -b cookies.txt http://localhost:8888/inventory
```

Result:

```http
HTTP/1.1 200 OK
```

After the query fix, inventory returned one row per product with no duplicate supplier rows.

Status: Passed.

## Inventory Alerts Route

Command:

```cmd
curl.exe -i -b cookies.txt http://localhost:8888/inventory/alerts
```

Result:

```http
HTTP/1.1 200 OK
```

Expected behavior:

```text
Only LOW_STOCK and OUT_OF_STOCK items are returned.
```

Status: Passed.

## Purchase Lots Route

Without cookie:

```cmd
curl.exe -i http://localhost:8888/purchase-lots
```

Result: blocked as unauthenticated.

With owner cookie:

```cmd
curl.exe -i -b cookies.txt http://localhost:8888/purchase-lots
```

Result:

```http
HTTP/1.1 200 OK
```

Status: Passed.

## Promotions Route

Without cookie:

```cmd
curl.exe -i http://localhost:8888/promotions
```

Result: blocked as unauthenticated.

With owner cookie:

```cmd
curl.exe -i -b cookies.txt http://localhost:8888/promotions
```

Result:

```http
HTTP/1.1 200 OK
```

Status: Passed.

## Reviews Route

Without cookie:

```cmd
curl.exe -i http://localhost:8888/reviews
```

Result: blocked as unauthenticated.

With owner cookie:

```cmd
curl.exe -i -b cookies.txt http://localhost:8888/reviews
```

Result:

```http
HTTP/1.1 200 OK
```

Status: Passed.

## Dashboard Route

Without cookie:

```cmd
curl.exe -i http://localhost:8888/dashboard/stock/general
```

Result: blocked as unauthenticated.

With owner cookie:

```cmd
curl.exe -i -b cookies.txt http://localhost:8888/dashboard/stock/general
```

Result:

```http
HTTP/1.1 200 OK
```

Observed response included:

```json
{
  "stock_health_summary": "CRITICAL"
}
```

Status: Passed.

## Cost Rollup Route

Without cookie:

```cmd
curl.exe -i http://localhost:8888/cost-rollup
```

Result: blocked as unauthenticated.

With owner cookie but missing id:

```cmd
curl.exe -i -b cookies.txt http://localhost:8888/cost-rollup
```

Result:

```json
{
  "error": "Validation failed",
  "fields": {
    "id": ["id is required"]
  }
}
```

With owner cookie and id:

```cmd
curl.exe -i -b cookies.txt "http://localhost:8888/cost-rollup?id=1"
```

Result:

```json
{
  "message": "Cost rollup calculated successfully",
  "data": {
    "product_id": 1,
    "total_material_cost": 157040.6,
    "unique_components": 14
  }
}
```

Status: Passed.
