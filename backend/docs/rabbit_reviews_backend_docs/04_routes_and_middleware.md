# Reviews Routes and Middleware

## Imports

```php
use App\Controllers\ReviewController;
use App\Controllers\CatalogReviewController;
```

## Internal Moderation Routes

These should require authentication.

```php
$router->getRoute('/reviews', [ReviewController::class, 'index'])
       ->only([AuthMiddleware::class]);

$router->patchRoute('/reviews/{id}/status', [ReviewController::class, 'updateStatus'])
       ->only([AuthMiddleware::class]);

$router->getRoute('/reviews/{id}', [ReviewController::class, 'show'])
       ->only([AuthMiddleware::class]);

$router->deleteRoute('/reviews/{id}', [ReviewController::class, 'destroy'])
       ->only([AuthMiddleware::class]);
```

## Catalogue Routes

Catalogue read endpoints are public/client-safe.

Review submission requires authentication.

```php
$router->postRoute('/catalog/products/{id}/reviews', [CatalogReviewController::class, 'store'])
       ->only([AuthMiddleware::class]);

$router->getRoute('/catalog/products/{id}/reviews', [CatalogReviewController::class, 'index']);

$router->getRoute('/catalog/products/{id}/rating-summary', [CatalogReviewController::class, 'ratingSummary']);
```

## Route Order

Put specific catalogue routes before the generic product detail route.

Recommended order:

```php
$router->getRoute('/catalog/products/{id}/relations', [CatalogProductRelationController::class, 'index']);
$router->getRoute('/catalog/products/{id}/promotions', [CatalogPromotionController::class, 'productPromotions']);

$router->postRoute('/catalog/products/{id}/reviews', [CatalogReviewController::class, 'store'])
       ->only([AuthMiddleware::class]);

$router->getRoute('/catalog/products/{id}/reviews', [CatalogReviewController::class, 'index']);
$router->getRoute('/catalog/products/{id}/rating-summary', [CatalogReviewController::class, 'ratingSummary']);

$router->getRoute('/catalog/products/{id}', [CatalogProductController::class, 'show']);
```

Reason:

```text
/catalog/products/{id}
```

is generic and may catch requests too early if the router matches in order.

