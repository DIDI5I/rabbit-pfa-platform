# Rabbit — Promotions Backend Documentation

## Goal

The Promotions module allows Rabbit owners/admins to create promotional campaigns and attach products to them.

Promotions are useful for client-facing offers, product catalogue visibility, future demand signals for stock intelligence, future chatbot explanations, and future navigation actions such as “show active promotions”.

The backend has two layers:

```text
/promotions
→ internal owner/admin promotion management

/catalog/promotions/active
/catalog/products/{id}/promotions
→ client-safe catalogue promotion browsing
```

## Current Status

```text
✅ promotions table created
✅ promotion_products table created
✅ internal promotion CRUD works
✅ attach product to promotion works
✅ list promotion products works
✅ detach product from promotion works
✅ duplicate attach does not crash
✅ active catalogue promotions work
✅ product-specific catalogue promotions work
✅ client-safe promotion responses work
✅ tests passed
```

## Standard Response Rule

Rabbit responses must follow the project structure:

```json
{
  "message": "...",
  "data": {}
}
```

## Promotion Status Rules

Promotion status is calculated, not manually stored.

```text
DISABLED → is_active = 0
UPCOMING → is_active = 1 and starts_at > NOW()
EXPIRED  → is_active = 1 and ends_at < NOW()
ACTIVE   → is_active = 1 and starts_at <= NOW() and ends_at >= NOW()
```
