# Reviews V1 Testing

## Manual Seed Test

```sql
INSERT INTO product_reviews (
    component_id,
    user_id,
    rating,
    title,
    comment,
    status
) VALUES (
    24,
    2,
    5,
    'Good quality belt',
    'Worked well for our conveyor maintenance.',
    'pending'
);
```

## Internal Moderation Tests

```http
GET /reviews
GET /reviews?status=pending
GET /reviews?rating=5
GET /reviews?component_id=24
GET /reviews/{id}
```

Approve review:

```http
PATCH /reviews/{id}/status
Content-Type: application/json
```

```json
{
  "status": "approved"
}
```

Expected:

```text
Review status updated successfully
status = approved
```

## Catalogue Submission Test

```http
POST /catalog/products/24/reviews
Content-Type: application/json
```

```json
{
  "user_id": 2,
  "rating": 5,
  "title": "Good quality belt",
  "comment": "Worked well for our conveyor maintenance."
}
```

Expected:

```text
Review submitted successfully and is pending moderation
status = pending
```

## Catalogue Approved Reviews Test

```http
GET /catalog/products/24/reviews
```

Expected:

```text
Only approved reviews appear.
Pending and rejected reviews are hidden.
```

## Rating Summary Test

```http
GET /catalog/products/24/rating-summary
```

Expected:

```json
{
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
```

## Final Test Status

```text
✅ internal review list works
✅ review filters work
✅ status update works
✅ delete works
✅ catalogue review submission works
✅ pending moderation works
✅ approved-only catalogue listing works
✅ rating summary works
✅ tests passed
```

