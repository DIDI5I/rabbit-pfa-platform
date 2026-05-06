# Internal Review Moderation Endpoints

These endpoints are for internal/owner review management.

They should require authentication.

Later, when role middleware is available, they should be restricted to owner/admin roles.

## Endpoints

```http
GET    /reviews
GET    /reviews/{id}
PATCH  /reviews/{id}/status
DELETE /reviews/{id}
```

## GET /reviews

Returns paginated reviews for moderation.

Supported filters:

```text
status
component_id
user_id
rating
search
page
limit
```

Examples:

```http
GET /reviews
GET /reviews?status=pending
GET /reviews?rating=5
GET /reviews?component_id=24
GET /reviews?search=belt
```

Response shape:

```json
{
  "message": "Reviews fetched successfully",
  "data": {
    "items": [],
    "filters": {
      "status": "pending",
      "search": null,
      "component_id": null,
      "user_id": null,
      "rating": null
    },
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 1,
      "total_pages": 1
    },
    "summary": {
      "by_status": {
        "pending": 1,
        "approved": 0,
        "rejected": 0
      }
    }
  }
}
```

## PATCH /reviews/{id}/status

Updates moderation status.

Request:

```json
{
  "status": "approved"
}
```

Allowed statuses:

```text
pending
approved
rejected
```

Response:

```json
{
  "message": "Review status updated successfully",
  "data": {
    "id": 1,
    "status": "approved"
  }
}
```

## DELETE /reviews/{id}

Deletes a review.

Response:

```json
{
  "message": "Review deleted successfully",
  "data": {
    "id": 1
  }
}
```

