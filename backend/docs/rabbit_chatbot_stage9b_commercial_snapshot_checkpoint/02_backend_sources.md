# Backend Sources Used

## CatalogPromotionService

Inspected service:

```php
CatalogPromotionService::activeForProduct(int $productId)
```

Used for active product promotions.

Observed response shape:

```json
{
  "data": {
    "product_id": 1,
    "items": [],
    "summary": {
      "total": 0
    }
  }
}
```

## CatalogReviewService

Inspected service methods:

```php
CatalogReviewService::approvedList(int $productId)
CatalogReviewService::ratingSummary(int $productId)
```

Observed reviews response shape:

```json
{
  "data": {
    "product_id": 1,
    "items": [],
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 0,
      "total_pages": 0
    }
  }
}
```

Observed rating summary response shape:

```json
{
  "data": {
    "product_id": 1,
    "review_count": 0,
    "average_rating": null,
    "rating_distribution": {
      "1": 0,
      "2": 0,
      "3": 0,
      "4": 0,
      "5": 0
    }
  }
}
```
