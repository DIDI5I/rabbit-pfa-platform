# StockIntelligenceDashboardIntentDetector

The detector recognizes dashboard/global stock intelligence questions.

Examples:

```text
show stock intelligence summary
show stock intelligence dashboard
show reorder dashboard
show reorder summary
global stock intelligence
what models are being used in stock intelligence
model distribution
confidence distribution
why is most confidence ESTIMATE_ONLY?
why are there 3 reorder recommendations?
explain stock intelligence dashboard
```

French-supported phrases include:

```text
résumé intelligence stock
resume intelligence stock
tableau de bord stock
résumé réapprovisionnement
resume reapprovisionnement
```

## Detector Ordering Fix

There was a conflict with `StockIntelligenceExplanationIntentDetector`.

The phrase:

```text
explain stock intelligence dashboard
```

was initially routed to the single-product explanation tool.

Fix:

```php
new StockIntelligenceDashboardIntentDetector(),
new StockIntelligenceExplanationIntentDetector(),
```

Dashboard/global wording should be detected before product-specific stock explanation wording.
