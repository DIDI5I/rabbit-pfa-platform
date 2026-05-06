# Rabbit — Promotions Future Integration Notes

## Stock Intelligence

Promotions can later become a demand driver.

Possible future fields:

```text
active_promotion_count
upcoming_promotion_count
promotion_period_overlap
promotion_risk_flag
```

Do not automatically inflate demand in V1 unless the calculation is explicit and explained.

## Chatbot

The chatbot can use promotions to answer:

```text
Which products are currently on promotion?
Is this product discounted?
Show active offers.
Why might demand increase soon?
```

The chatbot should call only backend-exposed endpoints:

```http
GET /catalog/promotions/active
GET /catalog/products/{id}/promotions
```

For owner/internal users, it may also call:

```http
GET /promotions
GET /promotions/{id}/products
```

## Role Safety

Clients should only see active catalogue promotions.

Owner/admin can manage create, update, delete, attach products, detach products, and view all statuses.

Clients should not manage promotions.

## Frontend Handoff Note

The user is not responsible for frontend implementation.

Backend should provide clean API contracts, response examples, and route expectations for the frontend teammate.
