# PermissionGuard Update

## Problem

Before EntityResolver, tools required strict numeric IDs:

```php
'required_params' => ['product_id']
```

After EntityResolver, some tools can accept either:

```text
product_id OR product_ref
component_id OR component_ref
```

## Solution

`PermissionGuard` now supports:

```php
'required_params_any' => ['product_id', 'product_ref']
```

or:

```php
'required_params_any' => ['component_id', 'component_ref']
```

## Important

For resolver-enabled tools, remove old strict required params.

Bad:

```php
'required_params' => ['product_id'],
'required_params_any' => ['product_id', 'product_ref'],
```

Good:

```php
'required_params_any' => ['product_id', 'product_ref'],
```
