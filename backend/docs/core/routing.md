Include:

public/index.php
Router flow
URI normalization (IMPORTANT — had a bug here)
method handling

//**************************//

Cost Rollup Endpoint
1. Purpose

Calculates the total material cost of a product by walking through its BOM/dependency tree.

2. Endpoint
GET /cost-rollup?id={product_id}

Example:

http://localhost/rabbit-pfa-platformV2/backend/public/cost-rollup?id=3
3. Architecture Flow
Frontend / Postman
        ↓
public/index.php
        ↓
Router
        ↓
CostRollupController@show
        ↓
CostRollupRequest
        ↓
CostRollupRepository
        ↓
Database
        ↓
JSON Response
4. Files Involved
app/Controllers/CostRollupController.php
app/Forms/CostRollupRequest.php
app/Repositories/CostRollupRepository.php
app/Support/Validator.php
app/Exceptions/ValidationException.php
5. Functionality
Reads product ID from query string
Validates product ID
Builds recursive BOM tree
Ignores phantom dependencies
Prevents circular loops
Limits recursion depth to 10
Calculates total preferred supplier material cost
Counts unique components
6. Request Parameters
id → required integer
7. Response Structure
{
  "product_id": 3,
  "total_material_cost": 123.45,
  "unique_components": 4
}
8. Tests
Test 1 — Valid product
GET /cost-rollup?id=3

Expected:

Returns product_id, total_material_cost, unique_components
Test 2 — Missing ID
GET /cost-rollup

Expected:

{
  "message": "Validation failed",
  "errors": {
    "id": ["id is required"]
  }
}
Test 3 — Invalid ID
GET /cost-rollup?id=abc

Expected:

{
  "message": "Validation failed",
  "errors": {
    "id": ["id must be an integer"]
  }
}
Test 4 — Product with no children
GET /cost-rollup?id=8

Expected:

{
  "product_id": 8,
  "total_material_cost": 0,
  "unique_components": 0
}
Test 5 — Non-existing product
GET /cost-rollup?id=999999

Expected currently:

{
  "product_id": 999999,
  "total_material_cost": 0,
  "unique_components": 0
}

Future improvement:

Return 404 Product not found
Test 6 — Wrong method
POST /cost-rollup?id=3

Expected:

{
  "error": "Method not allowed"
}
9. Edge Cases
Missing product ID
Invalid product ID
Product with no dependencies
Product with circular dependency
Dependency depth greater than 10
Components without preferred supplier
Phantom dependency should be ignored
10. Future Work
Add CostRollupService
Return consistent response format with message + data
Detect missing preferred supplier costs
Return 404 for non-existing product
Add detailed cost breakdown endpoint
Add labor cost and overhead cost later