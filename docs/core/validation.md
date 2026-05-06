Include:

Validator
ValidationException
how errors propagate
how forms use validator

//*************************//

Validation System
1. Purpose
The validation system ensures that incoming request data is correct, safe, and properly formatted before reaching business logic or the database.

2. Architecture Flow
Controller    ↓Form Request (e.g., LoginRequest, ProductRequest)    ↓Validator    ↓ValidationException (if errors exist)    ↓Global Exception Handler    ↓JSON Error Response

3. Files Involved
app/Forms/LoginRequest.phpapp/Forms/ProductRequest.phpapp/Forms/CostRollupRequest.phpapp/Support/Validator.phpapp/Exceptions/ValidationException.php

4. How Validation Works
Step 1 — Controller receives request
$request = new LoginRequest($_POST);$request->validate();

Step 2 — Form Request calls Validator
Validator::make($this->data)    ->required('email')    ->email('email')    ->required('password')    ->min('password', 6)    ->validate();

Step 3 — Validator collects errors
If any rule fails:
Errors are stored in an array

Step 4 — Exception is thrown
throw new ValidationException($errors);

Step 5 — Global handler returns JSON
{  "message": "Validation failed",  "errors": {    "email": ["email is required"]  }}

5. Available Validation Rules
required
Field must exist and not be empty

email
Must be a valid email format

min
Minimum string length
Example:
password must be at least 6 characters

integer
Must be a valid integerOptional fields are ignored if empty

numeric
Must be a numberOptional fields are ignored if empty

in
Value must exist in a predefined list
Example:
category must be one of:assembly, sub_assembly, component, raw_material

6. Form Request Responsibility
Each Form Request:
Receives raw inputValidates using ValidatorProvides clean getter methodsDoes NOT contain business logicDoes NOT access database

7. Example — LoginRequest
Validation rules:
email:  required  valid emailpassword:  required  minimum length 6

8. Example — ProductRequest
Validation rules:
page → integerlimit → integercategory → in allowed listmin_price → numericmax_price → numericmin_supplier_rating → numeric

9. Tests
Test 1 — Missing required field
POST /login
Body:
password=123456
Expected:
{  "message": "Validation failed",  "errors": {    "email": ["email is required"]  }}

Test 2 — Invalid email
POST /login
Body:
email=wrongemailpassword=123456
Expected:
Validation error for email

Test 3 — Short password
POST /login
Body:
password=123
Expected:
Validation error (min length)

Test 4 — Invalid integer
GET /products?page=abc
Expected:
{  "message": "Validation failed",  "errors": {    "page": ["page must be an integer"]  }}

Test 5 — Invalid category
GET /products?category=random
Expected:
Validation error (invalid category)

Test 6 — Optional fields
GET /products
Expected:
No validation errorOptional fields ignored

10. Edge Cases
Empty stringsMissing fieldsInvalid data typesVery long inputsUnexpected query parameters

11. Design Principles
Validation is centralizedControllers remain cleanErrors are consistent across all endpointsNo duplication of validation logic

12. Future Work
Add max() ruleAdd boolean() ruleAdd array() validationAdd nested validation (objects)Add custom error messagesAdd localization (multi-language errors)