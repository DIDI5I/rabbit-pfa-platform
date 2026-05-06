Include:

why ProductQuery.php exists
separation of concerns
SQL abstraction
has_children
node_role
regression tests

//*************************//

Product Query Refactor
1. Purpose

This refactor prevents ProductRepository.php from becoming too large by moving long SQL-building logic into a dedicated query helper:

app/Queries/ProductQuery.php
2. Problem Before Refactor

Before this refactor, ProductRepository was responsible for too many things:

building SELECT fields
building WHERE filters
counting products
fetching products
normalizing response data

This made the repository harder to read and harder to extend.

3. New Responsibility Split
ProductRepository
  - executes queries
  - handles pagination result
  - normalizes database rows

ProductQuery
  - builds SELECT fields
  - builds WHERE clause
  - stores reusable SQL fragments
4. Architecture Flow
ProductController@index
        ↓
ProductRequest
        ↓
ProductRepository
        ↓
ProductQuery
        ↓
Database
5. Files Involved
app/Repositories/ProductRepository.php
app/Queries/ProductQuery.php
app/Forms/ProductRequest.php
6. ProductQuery Responsibilities
selectFields()
where(ProductRequest $request)
selectFields()

Returns reusable SQL fields:

basic product columns
stock_status
has_children
node_role
where()

Builds dynamic filters:

active_only
search
category
low_stock
7. Current Computed Fields
stock_status
has_children
node_role
8. Tests After Refactor
Test 1 — Products still load
GET /products

Expected:

Products return successfully
No SQL errors
No class loading errors
Test 2 — Computed fields appear
GET /products

Expected each product includes:

stock_status
has_children
node_role
Test 3 — Search still works
GET /products?search=vis

Expected:

Only matching products return
Test 4 — Category filter still works
GET /products?category=component

Expected:

All products have category = component
Test 5 — Low stock filter still works
GET /products?low_stock=true

Expected:

All products satisfy stock_qty <= low_stock_threshold
Test 6 — Pagination still works
GET /products?page=1&limit=5

Expected:

Maximum 5 products returned
pagination data is correct
9. Edge Cases
No products
Invalid filters
Products without dependencies
Products used as children
Products with children
10. Future Work

Add these into ProductQuery gradually:

supplier EXISTS filter
matched_source SQL
min_price filter
max_price filter
supplier rating filter
supplier country filter
sorting logic
11. Main Rule
Repository executes SQL.
Query class builds SQL.
Controller does not touch SQL.
Form validates request data.