# Register Endpoint

## Purpose
Creates a new user account.

Register does not start a session.  
Only login creates a session.

## Endpoint
POST /register

## Request
Content-Type: application/json

### Client / Owner
{
  "name": "Client User",
  "email": "client@test.com",
  "password": "123456",
  "role": "client"
}

### Fournisseur
{
  "name": "Supplier User",
  "email": "supplier@test.com",
  "password": "123456",
  "role": "fournisseur",
  "supplier_company_id": 1
}

## Rules
- email must be unique
- password minimum length = 6
- role must be owner, client, or fournisseur
- fournisseur requires supplier_company_id
- client/owner must not use supplier_company_id
- password is stored as password_hash
- no session is created

## Success Response
{
  "message": "User registered successfully"
}

## Validation Error
{
  "error": "Validation failed",
  "fields": {
    "email": ["Email already exists"]
  }
}