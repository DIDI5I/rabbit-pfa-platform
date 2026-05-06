# EntityResolver Return Shape

## Resolved Result

```php
[
    'status' => 'resolved',
    'entity_type' => 'product',
    'id' => 1,
    'match_type' => 'exact_sku',
    'query' => 'ASM-PMP-XR200',
    'matches' => [],
    'resolved_item' => [
        'id' => 1,
        'name' => 'Pompe centrifuge horizontale XR200 7.5 kW',
        'sku' => 'ASM-PMP-XR200',
        'category' => 'assembly',
        'availability_status' => 'LOW_AVAILABILITY',
    ],
    'original_params' => [
        'product_ref' => 'ASM-PMP-XR200',
    ],
]
```

## Needs Clarification Result

```php
[
    'status' => 'needs_clarification',
    'entity_type' => 'product',
    'id' => null,
    'match_type' => 'multiple_matches',
    'query' => 'xr200',
    'original_params' => [
        'product_ref' => 'xr200',
    ],
    'matches' => [
        ['id' => 16, 'name' => 'Arbre pompe inox AISI 420 Ø25 mm', 'sku' => 'CMP-SHAFT-PMP25'],
        ['id' => 10, 'name' => 'Kit maintenance pompe XR200', 'sku' => 'SUB-MNT-KIT-PMP'],
        ['id' => 1, 'name' => 'Pompe centrifuge horizontale XR200 7.5 kW', 'sku' => 'ASM-PMP-XR200'],
    ],
]
```
