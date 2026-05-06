# Rabbit — Client-Safe Catalogue Promotion Endpoints

These endpoints are for client-safe browsing.

They should not expose internal management fields such as:

```text
created_by
created_at
updated_at
inactive/disabled promotions
internal stock data
purchase costs
supplier unit costs
owner analytics
stock intelligence metadata
```

## Active Catalogue Promotions

```http
GET /catalog/promotions/active
```

Returns only promotions where:

```text
is_active = 1
starts_at <= NOW()
ends_at >= NOW()
```

Supports:

```text
page
limit
```

## Active Promotions for a Product

```http
GET /catalog/products/{id}/promotions
```

Example:

```http
GET /catalog/products/24/promotions
```

Response shape:

```json
{
  "message": "Catalog product promotions fetched successfully",
  "data": {
    "product_id": 24,
    "items": [
      {
        "id": 1,
        "title": "Hydraulic Components Spring Offer",
        "description": "Discount on selected hydraulic components.",
        "discount_type": "percentage",
        "discount_value": 10,
        "starts_at": "2026-05-01 00:00:00",
        "ends_at": "2026-06-01 23:59:59",
        "status": "ACTIVE"
      }
    ],
    "summary": {
      "total": 1
    }
  }
}
```

## Catalogue Safety Rule

Client-safe promotion endpoints should return only active promotions.

They should not return disabled, expired, or upcoming promotions unless a future frontend explicitly needs “upcoming offers.”
