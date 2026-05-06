# Known Issues and Deferred Cleanup

## 1. Auth Failure Status Codes

Currently unauthenticated protected routes return:

```http
422 Unknown Status Code
```

with a response like:

```json
{
  "error": "Validation failed",
  "fields": {
    "auth": ["User not authenticated."]
  }
}
```

Functionally this blocks access correctly, but semantically it should be changed later.

Recommended behavior:

```text
Unauthenticated -> 401 Unauthorized
Authenticated but wrong role -> 403 Forbidden
Validation failed -> 422 Unprocessable Entity
```

Priority: Medium.

This should be fixed before frontend polish and before exposing a formal chatbot API.

## 2. Response Format Inconsistency

Some endpoints still return:

```json
{
  "error": "Validation failed",
  "fields": {}
}
```

The preferred Rabbit API response style is:

```json
{
  "message": "Validation failed",
  "errors": {}
}
```

Success responses should remain:

```json
{
  "message": "...",
  "data": {}
}
```

Priority: Medium.

## 3. Cost Rollup Route Shape

Current route:

```http
GET /cost-rollup?id=1
```

This works, but a cleaner future route would be:

```http
GET /products/{id}/cost-rollup
```

Reason:

Cost rollup belongs to a product/component.

Priority: Low/Medium.

## 4. Multiple Preferred Sources in Database

The inventory query now protects against multiple preferred sources by selecting one source per component.

However, the underlying data can still contain more than one preferred source for a component.

Recommended service-level rule:

```php
UPDATE part_sources
SET is_preferred = 0
WHERE component_id = :component_id;

UPDATE part_sources
SET is_preferred = 1
WHERE id = :source_id;
```

Priority: Medium.

## 5. Session Auth vs Token Auth

The backend currently uses PHP session cookies.

This works for browser/frontend integration if cookies are configured correctly.

If the project later needs mobile apps or external API clients, bearer token auth may be needed.

Priority: Low for now.

## 6. Chatbot Not Started Yet

No chatbot/backend intelligence implementation has started yet.

Before implementation, define:

```text
route-permission registry
allowed chatbot tools
role-based endpoint access
strict grounding rules
```

Priority: Next stage.
