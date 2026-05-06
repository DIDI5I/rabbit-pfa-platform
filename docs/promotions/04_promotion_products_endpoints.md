# Rabbit — Promotion Products Endpoints

These endpoints manage which products belong to a promotion.

## List Products Attached to Promotion

```http
GET /promotions/{id}/products
```

Response shape:

```json
{
  "message": "Promotion products fetched successfully",
  "data": {
    "promotion_id": 1,
    "items": [
      {
        "promotion_product_id": 1,
        "promotion_id": 1,
        "component_id": 24,
        "created_at": "2026-05-04 10:00:00",
        "product": {
          "id": 24,
          "name": "Courroie trapézoïdale SPA 1250",
          "sku": "CMP-BELT-SPA1250",
          "description": "...",
          "category": "component",
          "unit_of_measure": "ea",
          "availability_status": "AVAILABLE",
          "is_active": true
        }
      }
    ],
    "summary": {
      "total": 1,
      "by_availability": {
        "AVAILABLE": 1
      },
      "by_category": {
        "component": 1
      }
    }
  }
}
```

## Attach Product to Promotion

```http
POST /promotions/{id}/products
Content-Type: application/json
```

Example:

```json
{
  "component_id": 24
}
```

Behavior:

```text
promotion must exist
component must exist
component must be active
duplicate attachment does not crash
```

## Detach Product from Promotion

```http
DELETE /promotions/{id}/products/{componentId}
```
