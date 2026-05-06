Product Filter Query
1. Purpose
The ProductFilterQuery class encapsulates all filtering logic for the products endpoint.
It prevents ProductQuery and ProductRepository from becoming overly complex by isolating dynamic WHERE clause construction.

2. Architecture Role
ProductController    ↓ProductRequest    ↓ProductRepository    ↓ProductQuery (SELECT fields)    ↓ProductFilterQuery (WHERE conditions)    ↓Database

3. Files Involved
app/Queries/ProductFilterQuery.phpapp/Queries/ProductQuery.phpapp/Forms/ProductRequest.phpapp/Repositories/ProductRepository.php

4. Responsibilities
Build WHERE clause dynamicallyBind parameters safelyEncapsulate filtering logicKeep repository clean

5. Supported Filters
Core Filters
active_onlysearchcategorylow_stock

Supplier Filters
supplier_idsupplier_namesupplier_countrymin_supplier_rating

Price Filters
min_pricemax_price

6. SQL Strategy
All supplier-related filters use:
EXISTS subqueries
Example:
EXISTS (    SELECT 1    FROM part_sources ps    WHERE ps.component_id = c.id      AND ps.unit_cost >= ?)

7. Why EXISTS is used
Avoids duplicate rowsKeeps main query simpleEfficient for filteringDoes not require JOIN complexity

8. Method Structure
Each filter is implemented as:
private methodchainableadds condition + parameterreturns self
Example:
private function supplierId(ProductRequest $request): self

9. Output Format
The class returns:
[    'sql' => 'WHERE ...',    'params' => [...]]

10. Example Query
GET /products?category=component&min_price=10&supplier_country=France
Generated WHERE:
WHERE c.category = ?AND EXISTS (    SELECT 1    FROM part_sources ps    WHERE ps.component_id = c.id      AND ps.unit_cost >= ?)AND EXISTS (    SELECT 1    FROM part_sources ps    JOIN suppliers s ON s.id = ps.supplier_id    WHERE ps.component_id = c.id      AND s.country LIKE ?)

11. Tests
Test 1 — Supplier ID filter
GET /products?supplier_id=1
Expected:
Only products supplied by supplier 1

Test 2 — Supplier name filter
GET /products?supplier_name=metal
Expected:
Products with supplier name containing "metal"

Test 3 — Supplier country filter
GET /products?supplier_country=France
Expected:
Products supplied from France

Test 4 — Supplier rating filter
GET /products?min_supplier_rating=4
Expected:
Products with suppliers rated >= 4

Test 5 — Minimum price
GET /products?min_price=10
Expected:
Products with at least one source >= 10

Test 6 — Maximum price
GET /products?max_price=100
Expected:
Products with at least one source <= 100

Test 7 — Price range
GET /products?min_price=10&max_price=100
Expected:
Products with sources between 10 and 100

Test 8 — Combined filters
GET /products?category=component&supplier_country=France&min_price=20
Expected:
All conditions satisfied simultaneously

12. Edge Cases
No matching suppliersProducts without part_sourcesConflicting filters (no results)Large filter combinationsNull supplier fields

13. Design Principles
Single responsibility: filtering onlyComposable: filters can be combined freelySafe: uses prepared parametersExtensible: new filters can be added easily

14. Future Work
Add sorting filters (price, rating, name)Add multiple supplier filtering (IN clause)Add aggregation filters (average cost)Add performance optimization if dataset grows