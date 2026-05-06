# ProductIntelligenceSnapshotService Changes

## Commercial Section Added

Added to `emptySnapshot()`:

```php
'commercial' => [
    'active_promotions_count' => 0,
    'active_promotions_preview' => [],

    'review_count' => 0,
    'average_rating' => null,
    'rating_distribution' => [
        '1' => 0,
        '2' => 0,
        '3' => 0,
        '4' => 0,
        '5' => 0,
    ],

    'reviews_preview' => [],
],
```

## Builder Added

Added:

```php
buildCommercial(int $productId, string $role, array &$snapshot): array
```

The method calls:

```php
(new CatalogPromotionService())->activeForProduct($productId)
(new CatalogReviewService())->ratingSummary($productId)
(new CatalogReviewService())->approvedList($productId)
```

## Sanitizers Added

Added:

```php
sanitizePromotions(array $promotions, int $limit): array
sanitizeReviews(array $reviews, int $limit): array
```

Promotion preview fields:

```text
title
description
discount_type
discount_value
starts_at
ends_at
```

Review preview fields:

```text
rating
title
comment
created_at
```

## Data Quality Flags

If commercial fetch fails, the service adds flags such as:

```text
promotions_fetch_failed
rating_summary_fetch_failed
reviews_fetch_failed
```

and missing sections such as:

```text
commercial.promotions
commercial.rating_summary
commercial.reviews
```
