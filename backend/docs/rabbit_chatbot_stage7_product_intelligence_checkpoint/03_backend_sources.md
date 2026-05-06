# Backend Sources Used

## CatalogProductService

Used for public-safe product identity:

```php
(new CatalogProductService())->findById($productId)
```

## RecommendationService

Used for product relationships/recommendations:

```php
(new RecommendationService())->forProduct($productId)
```

Returns explicit relations, compatible alternatives, accessories, spare parts, same-category items, same-supplier items, and grounding metadata.

## StockIntelligenceService

Used for owner-only reorder intelligence:

```php
(new StockIntelligenceService())->reorderRecommendations()
```

The snapshot service finds the matching product item by product ID.
