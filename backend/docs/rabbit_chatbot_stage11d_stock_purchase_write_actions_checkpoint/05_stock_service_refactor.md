# StockService Refactor

Stage 11D added:

```php
recordMovementByData(
    int $componentId,
    string $type,
    float $quantity,
    string $reason = 'MANUAL_ADJUSTMENT',
    ?string $referenceType = null,
    ?int $referenceId = null,
    ?string $notes = null,
    ?int $createdBy = null
)
```

This allows chatbot executors to call stock movement logic safely without faking HTTP request bodies.

The method still uses `StoreStockMovementRequest` validation internally.
