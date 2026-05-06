# Products API

## Endpoints

GET /products
GET /products/{id}
POST /products
PATCH /products/{id}
DELETE /products/{id}

## Filtering

Query params:
- page
- limit
- search
- category
- min_price
- max_price
- min_supplier_rating
- low_stock
- active_only

## Active Filter

Default:
GET /products → active_only = true

Owner override:
GET /products?active_only=false

Restriction:
Only owner can view inactive products

## Response

{
  "message": "Products fetched successfully",
  "data": {
    "items": [...],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 100,
      "total_pages": 10
    }
  }
}