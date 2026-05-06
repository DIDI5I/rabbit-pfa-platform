# Catalogue Search Fix

## Problem

The chatbot extracted:

```json
{
  "search": "pump"
}
```

but the backend returned unfiltered catalogue results.

## Root Cause

`CatalogProductService::list(array $filters = [])` read the filters, then overwrote them:

```php
$filters = $this->filtersFromQuery();
```

This discarded chatbot-provided filters.

## Correct Service Pattern

```php
public function list(array $filters = []): array
{
    $normalizedFilters = [
        'search' => $filters['search'] ?? null,
        'category' => $filters['category'] ?? null,
        'availability' => $filters['availability'] ?? null,
    ];

    $page = isset($filters['page'])
        ? max(1, (int) $filters['page'])
        : 1;

    $limit = isset($filters['limit'])
        ? max(1, min(50, (int) $filters['limit']))
        : 20;

    return $this->repository->list($normalizedFilters, $page, $limit);
}
```

## Correct Controller Pattern

```php
public function index(): array
{
    return $this->service->list($_GET);
}
```

## Correct Chatbot Pattern

```php
$filters = [
    'search' => $params['search'] ?? null,
    'category' => $params['category'] ?? null,
    'availability' => $params['availability'] ?? null,
    'page' => $params['page'] ?? 1,
    'limit' => $params['limit'] ?? 20,
];

$response = $service->list($filters);
```

## Design Rule

```text
Controllers pass request data.
Chatbot passes parsed safe params.
Services accept explicit arguments.
Services should not depend on hidden global request state.
```
