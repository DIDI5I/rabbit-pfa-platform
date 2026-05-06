# Rabbit — Promotions Files and Routes

## Query Files

```text
app/Queries/PromotionQuery.php
app/Queries/PromotionProductQuery.php
app/Queries/CatalogPromotionQuery.php
```

## Form Request Files

```text
app/Forms/StorePromotionRequest.php
app/Forms/UpdatePromotionRequest.php
```

## Repository Files

```text
app/Repositories/PromotionRepository.php
app/Repositories/PromotionProductRepository.php
app/Repositories/CatalogPromotionRepository.php
```

## Service Files

```text
app/Services/PromotionService.php
app/Services/PromotionProductService.php
app/Services/CatalogPromotionService.php
```

## Controller Files

```text
app/Controllers/PromotionController.php
app/Controllers/PromotionProductController.php
app/Controllers/CatalogPromotionController.php
```

## Routes Added

Internal promotion management:

```php
$router->get('/promotions', [PromotionController::class, 'index']);
$router->get('/promotions/{id}', [PromotionController::class, 'show']);
$router->post('/promotions', [PromotionController::class, 'store']);
$router->patch('/promotions/{id}', [PromotionController::class, 'update']);
$router->delete('/promotions/{id}', [PromotionController::class, 'destroy']);
```

Promotion products:

```php
$router->get('/promotions/{id}/products', [PromotionProductController::class, 'index']);
$router->post('/promotions/{id}/products', [PromotionProductController::class, 'store']);
$router->delete('/promotions/{id}/products/{componentId}', [PromotionProductController::class, 'destroy']);
```

Catalogue promotion endpoints:

```php
$router->get('/catalog/promotions/active', [CatalogPromotionController::class, 'active']);
$router->get('/catalog/products/{id}/promotions', [CatalogPromotionController::class, 'productPromotions']);
```

## Route Order Note

Put `/catalog/products/{id}/promotions` before `/catalog/products/{id}` if the router is order-sensitive.

Put `/promotions/{id}/products` before `/promotions/{id}` if the router is order-sensitive.
