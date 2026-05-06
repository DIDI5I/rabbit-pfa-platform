# Rabbit — Recommendation Aggregation Endpoint V1 Documentation

## 1. Goal

Recommendation V1 provides backend-grounded industrial product recommendations for Rabbit.

This is not an AI recommendation system and not an Amazon-style “people also bought” system.

Rabbit recommendations come only from existing backend data:

```text
dependencies
components.category
part_sources.supplier_id
```

The endpoint must never invent recommendations.

---

## 2. Main Endpoint

```http
GET /products/{id}/recommendations
```

Example:

```http
GET /products/1/recommendations
```

Route:

```php
$router->getRoute('/products/{id}/recommendations', [RecommendationController::class, 'show'])
       ->only([AuthMiddleware::class]);
```

Important route order:

```php
// PRODUCT RECOMMENDATIONS
$router->getRoute('/products/{id}/recommendations', [RecommendationController::class, 'show'])
       ->only([AuthMiddleware::class]);

// PRODUCT DETAIL
$router->getRoute('/products/{id}', [ProductController::class, 'show'])
       ->only([AuthMiddleware::class]);
```

The recommendations route must be placed before `/products/{id}` so the router does not capture `/recommendations` as part of the generic product-detail route.

---

## 3. Standard Rabbit Response Format

All Rabbit endpoints must follow:

```json
{
  "message": "...",
  "data": {}
}
```

Recommendation endpoint response shape:

```json
{
  "message": "Product recommendations fetched successfully",
  "data": {
    "product": {},
    "recommendations": {
      "explicit_relations": [],
      "compatible_alternatives": [],
      "accessories": [],
      "spare_parts": [],
      "same_category": [],
      "same_supplier": []
    },
    "meta": {}
  }
}
```

If product does not exist:

```json
{
  "message": "Product recommendations fetched successfully",
  "data": {
    "product": null,
    "recommendations": {
      "explicit_relations": [],
      "compatible_alternatives": [],
      "accessories": [],
      "spare_parts": [],
      "same_category": [],
      "same_supplier": []
    }
  }
}
```

---

## 4. Recommendation Categories

### explicit_relations

Source:

```text
dependencies
```

Meaning:

```text
Products directly related to the selected product through dependency/relation rows.
```

Includes relation metadata:

```text
dependency_id
parent_id
child_id
qty_required
relation_type
is_phantom
```

### compatible_alternatives

Source:

```text
dependencies.relation_type = compatible_alternative
```

Meaning:

```text
Products explicitly marked as compatible alternatives.
```

Client-facing AI/product comparison later must only suggest compatible alternatives if they come from explicit backend relations.

### accessories

Source:

```text
dependencies.relation_type = accessory
```

### spare_parts

Source:

```text
dependencies.relation_type IN ('spare_part', 'replacement_part')
```

### same_category

Source:

```text
components.category
```

### same_supplier

Source:

```text
part_sources.supplier_id
```

Meaning:

```text
Other active products from the same preferred supplier.
```

Important fix:

```text
same_supplier should use the selected product's preferred supplier only.
```

The SQL must include:

```sql
source_ps.is_preferred = 1
```

---

## 5. Files Created

```text
app/Queries/RecommendationQuery.php
app/Repositories/RecommendationRepository.php
app/Services/RecommendationService.php
app/Controllers/RecommendationController.php
```

Route file updated with:

```php
use App\Controllers\RecommendationController;
```

---

## 6. RecommendationQuery.php

Created methods:

```php
public static function findProduct(): string
public static function explicitRelations(): string
public static function sameCategoryProducts(int $limit = 6): string
public static function sameSupplierProducts(int $limit = 6): string
```

### findProduct()

Finds the selected active product and returns:

```text
id
name
sku
description
category
unit_of_measure
stock_qty
low_stock_threshold
is_active
stock_status
matched_source
```

### explicitRelations()

Fetches dependency/relation rows where:

```sql
d.parent_id = ?
```

and joins child products.

### sameCategoryProducts()

Fetches other active products with the same category:

```sql
WHERE c.category = ?
AND c.id != ?
AND c.is_active = 1
```

Limit is injected safely after sanitization:

```php
$limit = max(1, min($limit, 20));
```

Reason:

```text
The query binder quoted LIMIT ? as '6', causing MySQL syntax error.
```

### sameSupplierProducts()

Fetches products sharing the same preferred supplier.

Correct WHERE section:

```sql
WHERE source_ps.component_id = ?
AND source_ps.is_preferred = 1
AND c.id != ?
AND c.is_active = 1
AND ps.is_preferred = 1
```

---

## 7. RecommendationRepository.php

Created methods:

```php
public function findProduct(int $productId): ?array
public function explicitRelations(int $productId): array
public function sameCategoryProducts(string $category, int $productId, int $limit = 6): array
public function sameSupplierProducts(int $productId, int $limit = 6): array
```

The repository casts:

```text
id → int
stock_qty → float
low_stock_threshold → float
is_active → bool
matched_source.supplier_id → int
matched_source.unit_cost → float
dependency_id → int
parent_id → int
child_id → int
qty_required → float
is_phantom → bool
```

---

## 8. RecommendationService.php

Main method:

```php
public function forProduct(int $productId): array
```

Responsibilities:

```text
1. Fetch selected product.
2. Fetch explicit relations.
3. Fetch same-category products.
4. Fetch same-supplier products.
5. Group explicit relations.
6. Remove duplicates from same-category and same-supplier recommendations.
7. Return Rabbit response format.
```

Grouped relation logic:

```text
compatible_alternatives = compatible_alternative
accessories = accessory
spare_parts = spare_part or replacement_part
```

Grounding metadata:

```json
"meta": {
  "grounding": {
    "explicit_relations": "dependencies",
    "same_category": "components.category",
    "same_supplier": "part_sources.supplier_id"
  },
  "note": "Recommendations are generated from backend relations and supplier/category data only. No AI guessing is used."
}
```

---

## 9. RecommendationController.php

```php
<?php

namespace App\Controllers;

use App\Services\RecommendationService;

class RecommendationController
{
    private RecommendationService $recommendationService;

    public function __construct()
    {
        $this->recommendationService = new RecommendationService();
    }

    public function show(int $id): array
    {
        return $this->recommendationService->forProduct($id);
    }
}
```

---

## 10. Tested Result

Tested endpoint:

```http
GET /products/1/recommendations
```

Returned:

```text
Product: Pompe centrifuge horizontale XR200 7.5 kW
SKU: ASM-PMP-XR200
Category: assembly
Preferred supplier: Maghreb Pumps & Rotating Equipment
```

Returned groups:

```text
explicit_relations ✅
spare_parts ✅
same_category ✅
same_supplier ✅
compatible_alternatives empty for this product
accessories empty for this product
```

Example explicit relations:

```text
Cartouche hydraulique pompe DN50
Pack moteur 7.5 kW avec accouplement
Joint mécanique 25 mm Carbon/SiC EPDM
Roulement 6205-2RS-C3
Kit joints toriques EPDM métriques
Kit maintenance pompe XR200
Pompe centrifuge horizontale XR300 11 kW
```

---

## 11. Bugs Found and Fixed

### MySQL LIMIT binding error

Error:

```text
SQLSTATE[42000]: Syntax error near ''6''
```

Cause:

```text
LIMIT ? was bound as '6'
```

Fix:

```php
public static function sameCategoryProducts(int $limit = 6): string
{
    $limit = max(1, min($limit, 20));
    return "... LIMIT {$limit}";
}
```

Same fix applied to `sameSupplierProducts()`.

### Bound variable mismatch

Error:

```text
SQLSTATE[HY093]: Invalid parameter number
```

Cause:

```text
Repository still passed $limit as a third bound parameter after LIMIT ? was removed.
```

Fix:

```php
[$category, $productId]
```

instead of:

```php
[$category, $productId, $limit]
```

and:

```php
[$productId, $productId]
```

instead of:

```php
[$productId, $productId, $limit]
```

### same_supplier included secondary suppliers

Observed:

```text
Product 1 preferred supplier = supplier_id 2
same_supplier returned some supplier_id 5 products
```

Cause:

```text
sameSupplierProducts() joined through all supplier links, not only preferred source.
```

Fix:

```sql
AND source_ps.is_preferred = 1
```

---

## 12. Frontend Usage

Suggested French section labels:

```js
const RECOMMENDATION_SECTION_LABELS = {
  explicit_relations: "Relations explicites",
  compatible_alternatives: "Alternatives compatibles",
  accessories: "Accessoires",
  spare_parts: "Pièces de rechange",
  same_category: "Même catégorie",
  same_supplier: "Même fournisseur",
};
```

Relation type labels:

```js
const RELATION_TYPE_LABELS = {
  technical_structure: "Structure technique",
  spare_part: "Pièce de rechange",
  replacement_part: "Pièce de remplacement",
  compatible_alternative: "Alternative compatible",
  accessory: "Accessoire",
  related_product: "Produit lié",
};
```

If `is_phantom = true`, show badge:

```text
Fantôme
```

---

## 13. Role / AI Safety Notes

Recommendation V1 is safe for authenticated users because it uses visible product/relation data.

For the future AI/chatbot layer:

```text
Client-facing AI must not invent product comparisons.
Client-facing AI may only compare/suggest products using exposed backend relations:
GET /products/{id}/dependencies
GET /products/{id}/recommendations
```

No free-form AI recommendations.

Owner-facing AI may use richer analytics later, but still must ground responses in backend endpoints.

Supplier-facing AI should not access owner analytics, ABC/Gini, purchase lot costs, or unrelated supplier data.

---

## 14. Current Status

Recommendation aggregation endpoint V1:

```text
implemented ✅
tested ✅
working ✅
minor same_supplier preferred-source fix recommended/applied ✅
```

Current Rabbit backend progress after this module:

```text
products/catalog ✅
product filtering/search ✅
dependencies ✅
stock movements ✅
inventory ✅
orders basic ✅
RFQs basic ✅
purchase lots/cost intelligence ✅
dashboard stock V1 ✅
ABC/Gini ✅
recommendations V1 ✅
partial role hardening ✅
```

---

## 15. Recommended Next Stage

Next practical stages:

```text
1. Notification queue
2. Forecasting / reorder recommendations
3. Finish RFQ → draft purchase lot → finalize purchase lot → stock IN test
4. Continue role hardening
5. Final frontend API handoff update
```

Recommended next stage:

```text
Notification queue
```

Reason:

```text
Notifications make RFQs, purchase lots, low-stock alerts, and order status changes visible in the frontend.
They are useful for demo and connect many existing backend modules.
```
