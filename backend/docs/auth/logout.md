# Logout Endpoint

## Purpose
Destroys the current PHP session.

## Endpoint
POST /logout

## Request
No body required.

## Behavior
- clears session data
- destroys session
- user becomes unauthenticated

## Success Response
{
  "message": "Logout successful"
}