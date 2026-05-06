# Auth Flow

## Register
- creates user
- hashes password
- does NOT create session

## Login
- validates credentials
- verifies password
- creates session

## Logout
- destroys session

## Responses

Login:
{
  "message": "Login successful",
  "data": {
    "user": { ... }
  }
}

Register:
{
  "message": "User registered successfully"
}

Logout:
{
  "message": "Logout successful"
}