# Intent Detector Updates

## TextIntentUtils

Added helpers:

```php
extractReferenceAfterKeywords(string $text, array $keywords): ?string
extractSkuLikeToken(string $text): ?string
```

## OwnerProductIntentDetector

Now extracts:

```text
product_id
product_ref
```

Examples:

```text
show dependencies for product 1
show dependencies for ASM-PMP-XR200
show dependencies for XR200
show internal product Pompe centrifuge XR200
```

## OwnerStockProcurementIntentDetector

Now extracts:

```text
product_id / product_ref
component_id / component_ref
```

Examples:

```text
show purchase lots for product 1
show purchase lots for ASM-PMP-XR200
show stock movements for component 27
show stock movements for CMP-HYD-FLT10
```

## ClarificationIntentDetector

Runs early in detector order.

Detects replies only when clarification is pending.

Examples:

```text
1
product 1
component 27
ASM-PMP-XR200
first
third
option 2
```

## Recommended Detector Order

```text
HelpIntentDetector
ClarificationIntentDetector
NavigationIntentDetector
ShowMoreIntentDetector
InventoryIntentDetector
CostIntentDetector
ReorderIntentDetector
DashboardIntentDetector
OwnerProductIntentDetector
OwnerStockProcurementIntentDetector
CatalogExtraIntentDetector
PromotionIntentDetector
CatalogIntentDetector
```
