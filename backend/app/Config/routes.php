<?php

use App\Controllers\AuthController;
use App\Controllers\ChatController;
use App\Controllers\ProductController;
use App\Controllers\CostRollupController;
use App\Controllers\DependencyController;
use App\Controllers\RfqController;
use App\Controllers\PurchaseLotController;
use App\Controllers\OrderController;
use App\Controllers\DashboardStockController;
use App\Controllers\InventoryController;
use App\Controllers\StockController;
use App\Controllers\RecommendationController;
use App\Controllers\NotificationController;
use App\Controllers\StockIntelligenceController;
use App\Controllers\CatalogProductController;
use App\Controllers\CatalogProductRelationController;
use App\Controllers\PromotionController;
use App\Controllers\PromotionProductController;
use App\Controllers\CatalogPromotionController;
use App\Controllers\ReviewController;
use App\Controllers\CatalogReviewController;
use App\Controllers\ChatbotController;

use App\Middleware\AuthMiddleware;
use App\Middleware\OwnerMiddleware;
use App\Middleware\SupplierMiddleware;
use App\Middleware\GuestMiddleware;

/** @var $router App\Core\Router */

// AUTH
$router->postRoute('/login', [AuthController::class, 'login'])
       ->only([GuestMiddleware::class]);

$router->postRoute('/register', [AuthController::class, 'register'])
       ->only([GuestMiddleware::class]);

$router->postRoute('/logout', [AuthController::class, 'logout'])
       ->only([AuthMiddleware::class]);

// HEALTH
$router->getRoute('/health', function () {
    return [
        'status' => 'ok',
        'time' => date('Y-m-d H:i:s'),
    ];
});

// CHAT
$router->postRoute('/chat', [ChatController::class, 'handle'])
       ->only([AuthMiddleware::class]);

// PRODUCTS
$router->getRoute('/products', [ProductController::class, 'index'])
       ->only([OwnerMiddleware::class]);

$router->postRoute('/products', [ProductController::class, 'store'])
       ->only([OwnerMiddleware::class]);

// PRODUCT DEPENDENCIES
$router->getRoute('/products/{id}/dependencies', [DependencyController::class, 'index'])
       ->only([OwnerMiddleware::class]);

$router->postRoute('/products/{id}/dependencies', [DependencyController::class, 'store'])
       ->only([OwnerMiddleware::class]);

$router->patchRoute('/products/{id}/dependencies/{dependencyId}', [DependencyController::class, 'update'])
       ->only([OwnerMiddleware::class]);

$router->deleteRoute('/products/{id}/dependencies/{dependencyId}', [DependencyController::class, 'destroy'])
       ->only([OwnerMiddleware::class]);

// PRODUCT PURCHASE LOTS
$router->getRoute('/products/{productId}/purchase-lots', [PurchaseLotController::class, 'byProduct'])
       ->only([OwnerMiddleware::class]);
// PRODUCT RECOMMENDATIONS
$router->getRoute('/products/{id}/recommendations', [RecommendationController::class, 'show'])
       ->only([AuthMiddleware::class]);


// PRODUCT DETAIL / UPDATE / DELETE
$router->getRoute('/products/{id}', [ProductController::class, 'show'])
       ->only([OwnerMiddleware::class]);

$router->patchRoute('/products/{id}', [ProductController::class, 'update'])
       ->only([OwnerMiddleware::class]);

$router->deleteRoute('/products/{id}', [ProductController::class, 'destroy'])
       ->only([OwnerMiddleware::class]);

// COSTS
$router->getRoute('/cost-rollup', [CostRollupController::class, 'show'])
       ->only([OwnerMiddleware::class]);
// RFQs
$router->postRoute('/rfqs', [RfqController::class, 'create'])
       ->only([OwnerMiddleware::class]);

$router->getRoute('/rfqs', [RfqController::class, 'index'])
       ->only([OwnerMiddleware::class]);

// RFQ workflow — static routes before /rfqs/{id}
$router->postRoute('/rfqs/open', [RfqController::class, 'open'])
       ->only([OwnerMiddleware::class]);

$router->postRoute('/rfqs/accept', [RfqController::class, 'accept'])
       ->only([OwnerMiddleware::class]);

$router->postRoute('/rfqs/reject', [RfqController::class, 'reject'])
       ->only([OwnerMiddleware::class]);

$router->postRoute('/rfqs/expire', [RfqController::class, 'expire'])
       ->only([OwnerMiddleware::class]);

// RFQ specific nested routes before /rfqs/{id}
$router->getRoute('/rfqs/{id}/actions', [RfqController::class, 'actions'])
       ->only([OwnerMiddleware::class]);

$router->patchRoute('/rfqs/{id}/quote', [RfqController::class, 'quote'])
       ->only([SupplierMiddleware::class]);

$router->getRoute('/rfqs/{id}', [RfqController::class, 'show'])
       ->only([OwnerMiddleware::class]);


// STOCK INTELLIGENCE
$router->getRoute('/stock/intelligence/reorder-recommendations', [StockIntelligenceController::class, 'reorderRecommendations'])
       ->only([OwnerMiddleware::class]);
       
// STOCK
$router->postRoute('/stock/movements', [StockController::class, 'storeMovement'])
       ->only([OwnerMiddleware::class]);

$router->getRoute('/stock/{componentId}/movements', [StockController::class, 'movements'])
       ->only([OwnerMiddleware::class]);

$router->getRoute('/stock/{componentId}', [StockController::class, 'show'])
       ->only([OwnerMiddleware::class]);

// INVENTORY
$router->getRoute('/inventory/alerts', [InventoryController::class, 'alerts'])
       ->only([OwnerMiddleware::class]);

$router->getRoute('/inventory', [InventoryController::class, 'index'])
       ->only([OwnerMiddleware::class]);

// PURCHASE LOTS
$router->getRoute('/purchase-lots', [PurchaseLotController::class, 'index'])
       ->only([OwnerMiddleware::class]);

$router->postRoute('/purchase-lots', [PurchaseLotController::class, 'store'])
       ->only([OwnerMiddleware::class]);

$router->patchRoute('/purchase-lots/{id}/finalize', [PurchaseLotController::class, 'finalize'])
       ->only([OwnerMiddleware::class]);

$router->getRoute('/purchase-lots/{id}', [PurchaseLotController::class, 'show'])
       ->only([OwnerMiddleware::class]);

// ORDERS
$router->getRoute('/orders', [OrderController::class, 'index'])
       ->only([OwnerMiddleware::class]);

$router->postRoute('/orders', [OrderController::class, 'store'])
       ->only([AuthMiddleware::class]);

$router->patchRoute('/orders/{orderId}/status', [OrderController::class, 'updateStatus'])
       ->only([OwnerMiddleware::class]);

$router->getRoute('/orders/{orderId}', [OrderController::class, 'show'])
       ->only([AuthMiddleware::class]);

// DASHBOARD STOCK
$router->getRoute('/dashboard/stock/general', [DashboardStockController::class, 'general'])
       ->only([OwnerMiddleware::class]);

$router->getRoute('/dashboard/stock/products/{productId}/performance', [DashboardStockController::class, 'productPerformance'])
       ->only([OwnerMiddleware::class]);

$router->getRoute('/dashboard/stock/products-performance', [DashboardStockController::class, 'productsPerformance'])
       ->only([OwnerMiddleware::class]);

$router->getRoute('/dashboard/stock/abc', [DashboardStockController::class, 'abc'])
       ->only([OwnerMiddleware::class]);

$router->getRoute('/dashboard/stock/abc/{class}', [DashboardStockController::class, 'abcClass'])
       ->only([OwnerMiddleware::class]);

// NOTIFICATIONS
$router->getRoute('/notifications', [NotificationController::class, 'index'])
       ->only([AuthMiddleware::class]);

$router->getRoute('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
       ->only([AuthMiddleware::class]);

$router->patchRoute('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
       ->only([AuthMiddleware::class]);

$router->patchRoute('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
       ->only([AuthMiddleware::class]);

// CATALOG — public/client-safe read endpoints
$router->getRoute('/catalog/promotions/active', [CatalogPromotionController::class, 'active']);

$router->getRoute('/catalog/products', [CatalogProductController::class, 'index']);

$router->getRoute('/catalog/products/{id}/relations', [CatalogProductRelationController::class, 'index']);

$router->getRoute('/catalog/products/{id}/promotions', [CatalogPromotionController::class, 'productPromotions']);

$router->postRoute('/catalog/products/{id}/reviews', [CatalogReviewController::class, 'store'])
       ->only([AuthMiddleware::class]);

$router->getRoute('/catalog/products/{id}/reviews', [CatalogReviewController::class, 'index']);

$router->getRoute('/catalog/products/{id}/rating-summary', [CatalogReviewController::class, 'ratingSummary']);

$router->getRoute('/catalog/products/{id}', [CatalogProductController::class, 'show']);


// PROMOTIONS — internal/owner management endpoints
$router->getRoute('/promotions/{id}/products', [PromotionProductController::class, 'index'])
       ->only([OwnerMiddleware::class]);

$router->postRoute('/promotions/{id}/products', [PromotionProductController::class, 'store'])
       ->only([OwnerMiddleware::class]);

$router->deleteRoute('/promotions/{id}/products/{componentId}', [PromotionProductController::class, 'destroy'])
       ->only([OwnerMiddleware::class]);

$router->getRoute('/promotions', [PromotionController::class, 'index'])
       ->only([OwnerMiddleware::class]);

$router->postRoute('/promotions', [PromotionController::class, 'store'])
       ->only([OwnerMiddleware::class]);

$router->getRoute('/promotions/{id}', [PromotionController::class, 'show'])
       ->only([OwnerMiddleware::class]);

$router->patchRoute('/promotions/{id}', [PromotionController::class, 'update'])
       ->only([OwnerMiddleware::class]);

$router->deleteRoute('/promotions/{id}', [PromotionController::class, 'destroy'])
       ->only([OwnerMiddleware::class]);

// REVIEWS — internal/owner moderation
$router->getRoute('/reviews', [ReviewController::class, 'index'])
       ->only([OwnerMiddleware::class]);

$router->patchRoute('/reviews/{id}/status', [ReviewController::class, 'updateStatus'])
       ->only([OwnerMiddleware::class]);

$router->getRoute('/reviews/{id}', [ReviewController::class, 'show'])
       ->only([OwnerMiddleware::class]);

$router->deleteRoute('/reviews/{id}', [ReviewController::class, 'destroy'])
       ->only([OwnerMiddleware::class]);


//CHATBOT

$router->postRoute('/chatbot/ask', [ChatbotController::class, 'ask']);