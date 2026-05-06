# Tool Handler Updates

## Updated Tools

These tools now use EntityResolver:

```text
OwnerProductDetailsTool
OwnerProductDependenciesTool
PurchaseLotsByProductTool
StockMovementsByComponentTool
```

## Pattern

Instead of directly casting ID:

```php
$productId = (int) $params['product_id'];
```

tools now resolve:

```php
$resolution = (new EntityResolver())->resolveProduct($params);
```

For components:

```php
$resolution = (new EntityResolver())->resolveComponent($params);
```

## Success Data Should Include

```php
[
    'product_id' => $productId,
    'resolution' => $resolution,
    'resolved_item' => $resolution['resolved_item'] ?? null,
]
```

or:

```php
[
    'component_id' => $componentId,
    'resolution' => $resolution,
    'resolved_item' => $resolution['resolved_item'] ?? null,
]
```

This lets presenters use names/SKUs instead of just IDs.
