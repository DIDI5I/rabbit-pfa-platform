# Intent Refactor

## Reason

The original `IntentClassifier` was becoming too large.

## New Design

`IntentClassifier` now delegates to small detector classes:

```text
HelpIntentDetector
NavigationIntentDetector
InventoryIntentDetector
CostIntentDetector
ReorderIntentDetector
DashboardIntentDetector
PromotionIntentDetector
CatalogIntentDetector
```

Each detector has one responsibility.

## Detector Order

Sensitive/internal detectors must run before generic catalogue detection.

Current order:

```text
Help
Navigation
Inventory
Cost
Reorder
Dashboard
Promotion
Catalog
```

## Why Order Matters

Example:

```text
"how much does product 1 cost us?"
```

This contains the word `product`, but it should resolve to:

```text
cost_rollup
```

not:

```text
catalog_product_details
```

So `CostIntentDetector` must run before `CatalogIntentDetector`.

## Natural Language Improvements Added

Examples now supported:

```text
what products are available?
do you have pumps?
is there anything on promotion?
which items are low?
what needs restocking?
how much does product 1 cost us?
open inventory
```
