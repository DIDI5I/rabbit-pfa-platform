# Registry Updates for EntityResolver

## OwnerProductToolRegistry

Resolver-enabled tools:

```text
owner_product_details
owner_product_dependencies
```

Use:

```php
'required_params_any' => ['product_id', 'product_ref']
```

## OwnerStockProcurementToolRegistry

Resolver-enabled tools:

```text
purchase_lots_by_product
stock_movements_by_component
```

Use:

```php
'required_params_any' => ['product_id', 'product_ref']
```

for purchase lots.

Use:

```php
'required_params_any' => ['component_id', 'component_ref']
```

for stock movements.

## PublicToolRegistry

Added:

```text
clarification_response
```

Definition:

```php
'clarification_response' => [
    'roles' => ['guest', 'client', 'supplier', 'owner'],
    'tool' => 'clarification_response',
    'operation_type' => 'read_only',
    'sensitive' => false,
    'required_params' => ['message'],
]
```

The clarification response itself is not sensitive, but the re-executed original tool still goes through normal permission checks.
