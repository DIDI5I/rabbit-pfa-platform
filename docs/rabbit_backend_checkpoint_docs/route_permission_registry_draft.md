# Route Permission Registry Draft

This is a first draft for the future chatbot/backend intelligence permission registry.

The chatbot must only use routes listed in this registry.

## Public / Client-Safe Routes

| Endpoint | Method | Access | Purpose |
|---|---:|---|---|
| `/catalog/products` | GET | public/client/owner | Browse client-safe catalogue products |
| `/catalog/products/{id}` | GET | public/client/owner | View client-safe product details |
| `/catalog/products/{id}/relations` | GET | public/client/owner | View safe product relations/alternatives |
| `/catalog/promotions/active` | GET | public/client/owner | View active public promotions |
| `/catalog/products/{id}/promotions` | GET | public/client/owner | View active product promotions |
| `/catalog/products/{id}/reviews` | GET | public/client/owner | View approved product reviews |
| `/catalog/products/{id}/rating-summary` | GET | public/client/owner | View product rating summary |
| `/catalog/products/{id}/reviews` | POST | authenticated client/owner | Create product review |

## Owner-Only Routes

| Endpoint | Method | Access | Purpose |
|---|---:|---|---|
| `/products` | GET | owner | Internal product list with sensitive data |
| `/products/{id}` | GET | owner | Internal product detail |
| `/products` | POST | owner | Create product |
| `/products/{id}` | PATCH | owner | Update product |
| `/products/{id}` | DELETE | owner | Delete product |
| `/products/{id}/dependencies` | GET | owner | Internal dependency/BOM-style structure |
| `/products/{id}/dependencies` | POST | owner | Add dependency |
| `/products/{id}/dependencies/{dependencyId}` | PATCH | owner | Update dependency |
| `/products/{id}/dependencies/{dependencyId}` | DELETE | owner | Delete dependency |
| `/inventory` | GET | owner | Owner inventory overview |
| `/inventory/alerts` | GET | owner | Low/out-of-stock alerts |
| `/stock/{componentId}` | GET | owner | View stock for component |
| `/stock/{componentId}/movements` | GET | owner | View stock movement history |
| `/stock/movements` | POST | owner | Create stock movement |
| `/stock/intelligence/reorder-recommendations` | GET | owner | Owner-only reorder intelligence |
| `/dashboard/stock/general` | GET | owner | General stock dashboard |
| `/dashboard/stock/products-performance` | GET | owner | Product performance dashboard |
| `/dashboard/stock/products/{productId}/performance` | GET | owner | Single product stock performance |
| `/dashboard/stock/abc` | GET | owner | ABC stock analytics |
| `/dashboard/stock/abc/{class}` | GET | owner | ABC class breakdown |
| `/purchase-lots` | GET | owner | View purchase lots |
| `/purchase-lots` | POST | owner | Create purchase lot |
| `/purchase-lots/{id}` | GET | owner | View purchase lot |
| `/purchase-lots/{id}/finalize` | PATCH | owner | Finalize purchase lot |
| `/products/{productId}/purchase-lots` | GET | owner | Product purchase lot history |
| `/cost-rollup?id={id}` | GET | owner | Calculate product cost rollup |
| `/promotions` | GET | owner | Internal promotion management list |
| `/promotions` | POST | owner | Create promotion |
| `/promotions/{id}` | GET | owner | View internal promotion detail |
| `/promotions/{id}` | PATCH | owner | Update promotion |
| `/promotions/{id}` | DELETE | owner | Delete promotion |
| `/promotions/{id}/products` | GET | owner | View promotion product attachments |
| `/promotions/{id}/products` | POST | owner | Attach product to promotion |
| `/promotions/{id}/products/{componentId}` | DELETE | owner | Remove product from promotion |
| `/reviews` | GET | owner | Internal review moderation list |
| `/reviews/{id}` | GET | owner | Internal review detail |
| `/reviews/{id}/status` | PATCH | owner | Approve/reject review |
| `/reviews/{id}` | DELETE | owner | Delete review |
| `/rfqs` | GET | owner | View RFQs |
| `/rfqs` | POST | owner | Create RFQ |
| `/rfqs/open` | POST | owner | Open RFQ workflow action |
| `/rfqs/accept` | POST | owner | Accept RFQ workflow action |
| `/rfqs/reject` | POST | owner | Reject RFQ workflow action |
| `/rfqs/expire` | POST | owner | Expire RFQ workflow action |
| `/rfqs/{id}` | GET | owner | View RFQ detail |
| `/rfqs/{id}/actions` | GET | owner | View RFQ action history |

## Supplier Route

| Endpoint | Method | Access | Purpose |
|---|---:|---|---|
| `/rfqs/{id}/quote` | PATCH | supplier | Supplier quote submission/update |

## Authenticated User-Scoped Routes

| Endpoint | Method | Access | Purpose |
|---|---:|---|---|
| `/orders` | POST | authenticated | Create order |
| `/orders/{orderId}` | GET | authenticated scoped | View own order or owner view |
| `/notifications` | GET | authenticated scoped | View user notifications |
| `/notifications/unread-count` | GET | authenticated scoped | Count unread notifications |
| `/notifications/read-all` | PATCH | authenticated scoped | Mark all user notifications read |
| `/notifications/{id}/read` | PATCH | authenticated scoped | Mark one notification read |

## Chatbot Tool Rules

### Client/Public Chatbot May Use

```text
/catalog/products
/catalog/products/{id}
/catalog/products/{id}/relations
/catalog/promotions/active
/catalog/products/{id}/promotions
/catalog/products/{id}/reviews
/catalog/products/{id}/rating-summary
```

### Owner Chatbot May Use

```text
/inventory
/inventory/alerts
/stock/intelligence/reorder-recommendations
/dashboard/stock/general
/cost-rollup?id={id}
/purchase-lots
/products
/promotions
/reviews
/rfqs
```

### Chatbot Must Not Use Direct SQL

All chatbot answers must be grounded in approved backend endpoints.

If no endpoint exists, return:

```json
{
  "message": "Chatbot could not answer safely.",
  "data": {
    "answer": null,
    "reason": "missing_backend_endpoint"
  }
}
```
