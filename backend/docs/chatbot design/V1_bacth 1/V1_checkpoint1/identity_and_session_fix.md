# Identity and Session Fix

## Problem

The chatbot initially detected logged-in users as guests.

The old resolver expected:

```php
$_SESSION['user']
```

But `AuthService::login()` stores values separately:

```php
Session::set('user_id', (int) $user['id']);
Session::set('name', $user['name']);
Session::set('email', $user['email']);
Session::set('role', $user['role']);
Session::set('supplier_company_id', ...);
```

## Fix

`IdentityResolver` now reads from the same session keys:

```php
Session::get('user_id')
Session::get('name')
Session::get('email')
Session::get('role')
Session::get('supplier_company_id')
```

## Role Normalization

French supplier role is normalized:

```text
fournisseur → supplier
```

This keeps the chatbot registry consistent while preserving the raw role if needed.
