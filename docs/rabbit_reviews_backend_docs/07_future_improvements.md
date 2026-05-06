# Reviews V1 Future Improvements

## Replace user_id in Review Submission

Current V1 request:

```json
{
  "user_id": 2,
  "rating": 5
}
```

Future version should use the authenticated user from AuthMiddleware/session instead:

```text
review.user_id = authenticated_user.id
```

This prevents one user from submitting reviews as another user.

## Role Enforcement

Current internal moderation routes require authentication.

Future improvement:

```text
/reviews/* → owner/admin only
```

## Client Review Ownership

Possible future endpoints:

```http
GET    /me/reviews
PATCH  /me/reviews/{id}
DELETE /me/reviews/{id}
```

Rules:

```text
client can view own reviews
client can edit/delete own pending reviews
approved reviews may require re-moderation after edit
```

## Duplicate Review Policy

V1 does not enforce one review per user per product.

Future option:

```sql
UNIQUE KEY unique_product_user_review (component_id, user_id)
```

Decision needed later:

```text
Allow multiple reviews over time
or
allow only one review per client/product
```

## Chatbot Use

The future Rabbit chatbot can use review/rating endpoints to answer:

```text
What do clients think about this product?
What is the average rating?
Are there any approved reviews?
Show me products with poor feedback.
```

Rules:

```text
Only approved reviews should be used for client-facing answers.
Pending/rejected reviews are owner/internal moderation data.
```

