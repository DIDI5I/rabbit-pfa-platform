# Next Steps

## Immediate Cleanup

1. Fix `result_meta.filters` for promotions so it is an object/map rather than an empty JSON array.

Recommended:

```php
$this->listMeta($total, $shown, $pagination, [
    'status' => 'ACTIVE',
])
```

## Next Major Feature

Implement memory/show-more later.

Possible future memory shape:

```json
{
  "last_tool": "catalog_search",
  "last_filters": {
    "search": "pompe"
  },
  "last_page": 1,
  "next_page": 2
}
```

This would support:

```text
show more
next page
show me the rest
```

## Do Not Implement Yet

Avoid adding AI/API fallback until deterministic Batch 1 is stable and documented.

## Recommended Next Development Order

1. Small cleanup for `filters`.
2. Add `ChatbotLogger`.
3. Add `MemoryPolicy` and `MemoryManager`.
4. Implement “show more” using memory.
5. Expand Batch 2 catalogue tools:
   - product relations
   - product promotions
   - product reviews
   - rating summary
