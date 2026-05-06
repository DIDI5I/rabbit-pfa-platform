# Rabbit — Internal Promotion Management Endpoints

These endpoints are for owner/admin/internal management.

## List Promotions

```http
GET /promotions
```

Supports:

```text
page
limit
status
search
```

Examples:

```http
GET /promotions
GET /promotions?page=1&limit=20
GET /promotions?status=ACTIVE
GET /promotions?status=UPCOMING
GET /promotions?status=EXPIRED
GET /promotions?status=DISABLED
GET /promotions?search=hydraulic
```

## Get Promotion

```http
GET /promotions/{id}
```

## Create Promotion

```http
POST /promotions
Content-Type: application/json
```

Example body:

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

## Update Promotion

```http
PATCH /promotions/{id}
Content-Type: application/json
```

Example:

```json
{
  "title": "Updated Hydraulic Offer",
  "discount_value": 15,
  "is_active": true
}
```

## Delete Promotion

```http
DELETE /promotions/{id}
```

Deleting a promotion also removes its product links because `promotion_products.promotion_id` uses `ON DELETE CASCADE`.
