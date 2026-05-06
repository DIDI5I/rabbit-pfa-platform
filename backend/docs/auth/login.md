Include:

login flow
validation rules
AuthService behavior
error handling
tests

//*************************//

Login Endpoint
1. Purpose
The login endpoint authenticates a user using email and password and returns user information if credentials are valid.

2. Endpoint
POST /login
Example:
http://localhost/rabbit-pfa-platformV2/backend/public/login

3. Architecture Flow
Frontend / Postman        ↓public/index.php        ↓Router        ↓AuthController@login        ↓LoginRequest (validation)        ↓AuthService        ↓UserRepository        ↓Database        ↓JSON Response

4. Files Involved
app/Controllers/AuthController.phpapp/Forms/LoginRequest.phpapp/Services/AuthService.phpapp/Repositories/UserRepository.phpapp/Support/Validator.phpapp/Exceptions/ValidationException.php

5. Login Flow


User submits email and password.


Request is sent to /login via POST.


Router directs request to AuthController@login.


LoginRequest validates input.


AuthService retrieves user via UserRepository.


Password is verified.


If valid:


user data is returned


session may be initialized (if implemented)




If invalid:


error response is returned





6. Validation Rules
Defined in LoginRequest:
email:  - required  - must be a valid email formatpassword:  - required  - minimum length = 6 characters
Validation uses:
Validator → ValidationException → global handler

7. AuthService Behavior
The AuthService is responsible for:
- retrieving user by email- verifying password (password_verify)- checking account status (if implemented)- returning user data
Typical flow:
$user = UserRepository->findByEmail($email);if (!$user || !password_verify($password, $user['password_hash'])) {    return ['error' => 'Invalid credentials'];}return [    'message' => 'Login successful',    'user' => $user];

8. Error Handling
Validation Errors
Handled by ValidationException.
Example:
{  "message": "Validation failed",  "errors": {    "email": ["email is required"],    "password": ["password is required"]  }}

Authentication Errors
Returned by AuthService:
{  "error": "Invalid credentials"}

Method Errors
If wrong HTTP method is used:
{  "error": "Method not allowed"}

9. Request Structure
Content Type
application/x-www-form-urlencodedormultipart/form-data
Body
email=owner@rabbit.mapassword=your_password

10. Response Structure
Success
{  "message": "Login successful",  "user": {    "id": 1,    "name": "Admin Rabbit",    "email": "owner@rabbit.ma",    "role": "owner",    "company_name": "Rabbit MRO"  }}

Failure (Invalid Credentials)
{  "error": "Invalid credentials"}

Failure (Validation)
{  "message": "Validation failed",  "errors": {    "email": ["email must be a valid email"]  }}

11. Tests
Test 1 — Valid Login
POST /login
Body:
email=owner@rabbit.mapassword=correct_password
Expected:
Login successful + user data

Test 2 — Missing Email
POST /login
Body:
password=123456
Expected:
Validation error for email

Test 3 — Invalid Email Format
POST /login
Body:
email=wrongemailpassword=123456
Expected:
Validation error for email format

Test 4 — Missing Password
POST /login
Body:
email=owner@rabbit.ma
Expected:
Validation error for password

Test 5 — Short Password
POST /login
Body:
email=owner@rabbit.mapassword=123
Expected:
Validation error (min length)

Test 6 — Wrong Credentials
POST /login
Body:
email=owner@rabbit.mapassword=wrongpassword
Expected:
Invalid credentials

Test 7 — Wrong HTTP Method
GET /login
Expected:
Method not allowed

12. Edge Cases
Empty request bodyWhitespace in email fieldVery long email inputSQL injection attempt in email (should be safe via prepared statements)

13. Notes / Future Work
Add session handling or JWT authenticationAdd rate limiting (prevent brute force attacks)Add account lockout after multiple failed attemptsAdd email verification logicAdd password reset flow