Include:

/products endpoint
pagination
filters
stock logic
response structure
tests

//****************************//

Products Index Endpoint
1. Purpose

The products endpoint returns a paginated list of components/products with filtering, search, and structural metadata (BOM awareness).

It is the central data endpoint of the system.

2. Endpoint
GET /products

Example:

http://localhost/rabbit-pfa-platformV2/backend/public/products
3. Architecture Flow
Frontend / Postman
        ↓
public/index.php
        ↓
Router
        ↓
ProductController@index
        ↓
ProductRequest (validation)
        ↓
ProductRepository
        ↓
ProductQuery (SQL builder)
        ↓
Database
        ↓
JSON Response
4. Files Involved
app/Controllers/ProductController.php
app/Forms/ProductRequest.php
app/Repositories/ProductRepository.php
app/Queries/ProductQuery.php
app/Support/Validator.php
app/Exceptions/ValidationException.php
5. Functionality
Core Features
Pagination
Search (name, SKU, description)
Category filtering
Low stock filtering
Active-only filtering
Computed Fields
stock_status
has_children
node_role
6. Computed Field Explanation
stock_status
out  → stock_qty <= 0
low  → stock_qty <= low_stock_threshold
ok   → otherwise
has_children
true  → component is composed of other components
false → standalone component
node_role
top_level_assembly → has children, not used as child
subassembly        → has children and is used inside another product
leaf               → no children
7. Query Parameters
page
limit
search
category
low_stock
active_only
8. Validation Rules
page → integer
limit → integer (max 100)
category → must be one of:
  assembly, sub_assembly, component, raw_material
low_stock → boolean
active_only → boolean
9. Request Examples
Basic request
GET /products
Pagination
GET /products?page=1&limit=5
Search
GET /products?search=vis
Category filter
GET /products?category=component
Low stock filter
GET /products?low_stock=true
Combined filters
GET /products?category=component&low_stock=true&limit=5
10. Response Structure
Success
{
  "message": "Products fetched successfully",
  "data": [
    {
      "id": 8,
      "name": "Vis M8x25 inox",
      "sku": "VIS-M8X25",
      "category": "component",
      "stock_qty": 500,
      "low_stock_threshold": 100,
      "stock_status": "ok",
      "has_children": false,
      "node_role": "leaf"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 10,
    "total": 12,
    "total_pages": 2
  }
}
11. Tests
Test 1 — Basic fetch
GET /products

Expected:

Returns product list with pagination
Test 2 — Pagination
GET /products?page=1&limit=5

Expected:

Returns 5 items max
pagination.limit = 5
Test 3 — Search
GET /products?search=vis

Expected:

Returns products matching "vis"
Test 4 — Category filter
GET /products?category=component

Expected:

All results must have category = component
Test 5 — Low stock
GET /products?low_stock=true

Expected:

stock_qty <= low_stock_threshold
Test 6 — Invalid category
GET /products?category=random

Expected:

{
  "message": "Validation failed",
  "errors": {
    "category": ["invalid value"]
  }
}
Test 7 — Invalid page
GET /products?page=abc

Expected:

Validation error
Test 8 — has_children correctness
GET /products

Expected:

Products with dependencies → has_children = true
Products without → false
Test 9 — node_role correctness
GET /products

Expected:

Correct classification:
top_level_assembly / subassembly / leaf
Test 10 — Combined filters
GET /products?category=component&low_stock=true

Expected:

All products satisfy BOTH conditions
12. Edge Cases
Empty dataset
Large dataset (pagination correctness)
Very long search string
Null descriptions
Products with no dependencies
Circular dependency protection (handled in cost-rollup, not here)
13. Notes / Future Work
Add supplier filtering
Add matched_source (preferred supplier info)
Add price filters
Add supplier rating filter
Add supplier country filter
Add sorting (price, name, stock)
Add caching if dataset grows

//********new feature

Matched Source
Description

Each product includes information about its preferred supplier.

This is returned as:

"matched_source": {
  "supplier_id": number,
  "supplier_name": string,
  "unit_cost": number
}

If no preferred supplier exists:

"matched_source": null
Business Logic
A product can have multiple suppliers.
Only the preferred supplier is returned.
Preferred supplier is defined by:
part_sources.is_preferred = TRUE
SQL Behavior
Uses subquery with JOIN:
part_sources → suppliers
Returns JSON object using JSON_OBJECT()
LIMIT 1 ensures only one supplier is returned
Purpose
Provides immediate sourcing context for each product
Avoids additional API calls
Supports cost analysis and supplier comparison
Tests
Test — Product with preferred supplier
GET /products

Expected:

matched_source is NOT null
Test — Product without preferred supplier
GET /products

Expected:

matched_source = null
Test — Data integrity
Check:
supplier_id exists
supplier_name matches DB
unit_cost is correct


//*****************************************************************///
New Feature 

Product Show Endpoint
Purpose

Fetches a single product by ID with the same enriched fields used in the product list.

Route
GET /products/{id}

Example:

GET /products/8
Controller
ProductController@show
Service
ProductService@show
Repository
ProductRepository@findById
Query
ProductQuery::findById()
Response
{
  "message": "Product fetched successfully",
  "data": {
    "id": 8,
    "name": "Vis M8x25 inox",
    "has_children": false,
    "node_role": "leaf",
    "matched_source": null
  }
}
Tests
GET /products/8

Expected:

Returns one product
GET /products/999999

Expected:

{
  "error": "Product not found"
}

After documenting this, next refactor is:

POST /products → ProductController@store


//******************// 

PATCH /products/{id}

Purpose:
Updates selected product fields using JSON body.

Route:
PATCH /products/24

Controller:
ProductController@update

Form:
UpdateProductRequest

Service:
ProductService@update

Repository:
ProductRepository@update

Query:
ProductQuery::update()

Body example:
{
  "name": "Updated Test Bearing",
  "stock_qty": 50,
  "low_stock_threshold": 10
}

Expected:
Product updated successfully + refreshed product data.

Important:
This is a partial update. Fields not included remain unchanged.

//*****************************//

DELETE /products/{id}

Purpose:
Soft deletes a product by setting is_active = 0.

Important:
Product is not removed from database.
Used for data integrity and audit.

Response:
{
  "message": "Product deleted successfully"
}