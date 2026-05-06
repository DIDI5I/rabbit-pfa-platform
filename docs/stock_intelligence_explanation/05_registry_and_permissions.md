# Registry and Permissions

Registered tool:

```text
stock_intelligence_explanation
```

Role policy:

```text
owner only
```

Reason:

The output can expose sensitive internal information:

```text
current stock
thresholds
preferred supplier
supplier lead time
purchase cost
estimated reorder value
stock risk
reorder logic
```

Registry definition:

```php
'stock_intelligence_explanation' => [
    'roles' => ['owner'],
    'tool' => 'stock_intelligence_explanation',
    'operation_type' => 'read_only',
    'sensitive' => true,
    'required_params_any' => ['product_id', 'product_ref'],
]
```

Guest/client/supplier access must be denied.
