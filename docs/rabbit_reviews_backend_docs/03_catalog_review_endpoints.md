# Catalogue Review Endpoints

These endpoints are client-facing catalogue endpoints.

## Endpoints

```http
POST /catalog/products/{id}/reviews
GET  /catalog/products/{id}/reviews
GET  /catalog/products/{id}/rating-summary
```

## POST /catalog/products/{id}/reviews

Allows an authenticated user to submit a review.

Current V1 implementation accepts `user_id` in the body because authenticated-user extraction is not yet standardized across the snippets.

Later improvement:

```text
Replace request body user_id with authenticated user ID from AuthMiddleware/session.
```

Request:

```json
{
  "user_id": 2,
  "rating": 5,
  "title": "Good quality belt",
  "comment": "Worked well for our conveyor maintenance."
}
```

Response:

```json
{
  "message": "Review submitted successfully and is pending moderation",
  "data": {
    "id": 1,
    "component_id": 24,
    "user_id": 2,
    "rating": 5,
    "title": "Good quality belt",
    "comment": "Worked well for our conveyor maintenance.",
    "status": "pending"
  }
}
```

Important rule:

```text
New reviews are always pending by default.
```

## GET /catalog/products/{id}/reviews

Returns approved reviews only.

Pending and rejected reviews must not appear in the catalogue.

Response:

```json
{
  "message": "Catalog product reviews fetched successfully",
  "data": {
    "product_id": 24,
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

## GET /catalog/products/{id}/rating-summary

Returns rating statistics using approved reviews only.

Response:

```json
{
  "message": "Catalog product rating summary fetched successfully",
  "data": {
    "product_id": 24,
    "review_count": 1,
    "average_rating": 5,
    "rating_distribution": {
      "1": 0,
      "2": 0,
      "3": 0,
      "4": 0,
      "5": 1
    }
  }
}
```

