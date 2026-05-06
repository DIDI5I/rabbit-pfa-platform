# Rabbit — Reviews Backend V1 Documentation

## Status

Reviews V1 is implemented and tested.

```text
✅ product_reviews table created
✅ internal review moderation works
✅ clients can submit product reviews
✅ submitted reviews start as pending
✅ catalogue only shows approved reviews
✅ product rating summary works
✅ tests passed
```

## Purpose

The reviews module allows clients to submit star ratings and written reviews for catalogue products.

Reviews are moderated before becoming visible in the client catalogue.

```text
client submits review → pending
owner approves review → approved
owner rejects review → rejected
catalogue displays approved only
```

## Review Record

A review contains both the star rating and the written review content.

```json
{
  "component_id": 24,
  "user_id": 2,
  "rating": 5,
  "title": "Good quality belt",
  "comment": "Worked well for our conveyor maintenance.",
  "status": "pending"
}
```

No separate ratings table is required for V1.

