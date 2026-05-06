# Product Dependencies (BOM)

## Endpoints

GET    /products/{id}/dependencies
POST   /products/{id}/dependencies
PATCH  /products/{id}/dependencies/{childId}
DELETE /products/{id}/dependencies/{childId}

## Rules

- No self dependency
- No duplicate dependency
- No circular dependency
- Phantom dependencies allowed

## Validation Errors

{
  "error": "Validation failed",
  "fields": {
    "dependency": ["Circular dependency detected"]
  }
}