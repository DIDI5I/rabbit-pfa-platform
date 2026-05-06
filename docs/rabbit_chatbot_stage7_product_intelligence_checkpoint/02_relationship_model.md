# Product Relationship Model

The old dependency concept was reframed into product relationship intelligence.

Supported relationship types:

```text
technical_structure
replacement_part
compatible_part
compatible_alternative
spare_part
accessory
similar_product
commercial_alternative
frequently_bought_together
related_product
```

`internal_structure` was replaced with `technical_structure` to avoid confusion with manufacturing/BOM logic.

Relationships are for technical maintenance intelligence and commercial recommendation intelligence, not production.
