# Rabbit — Promotions Testing Checklist

## Database

```sql
SHOW TABLES LIKE 'promotions';
SHOW TABLES LIKE 'promotion_products';
```

## Create Promotion

```http
POST /promotions
```

Body:

```json
{
  "title": "Hydraulic Components Spring Offer",
  "description": "Discount on selected hydraulic components.",
  "discount_type": "percentage",
  "discount_value": 10,
  "starts_at": "2026-05-01 00:00:00",
  "ends_at": "2026-06-01 23:59:59",
  "is_active": true,
  "created_by": 1
}
```

## List Promotions

```http
GET /promotions
GET /promotions?status=ACTIVE
GET /promotions?search=hydraulic
```

## Attach Product

```http
POST /promotions/1/products
```

Body:

```json
{
  "component_id": 24
}
```

## List Promotion Products

```http
GET /promotions/1/products
```

## Detach Product

```http
DELETE /promotions/1/products/24
```

## Active Catalogue Promotions

```http
GET /catalog/promotions/active
```

## Product Active Promotions

```http
GET /catalog/products/24/promotions
```

## Current Test Status

```text
✅ promotions CRUD passed
✅ attach/list/detach promotion products passed
✅ active catalogue promotions passed
✅ product-specific catalogue promotions passed
```
