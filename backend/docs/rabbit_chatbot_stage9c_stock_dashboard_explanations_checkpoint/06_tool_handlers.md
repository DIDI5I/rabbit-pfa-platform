# Tool Handlers

## StockIntelligenceSummaryTool

Calls:

```php
(new StockIntelligenceService())->reorderRecommendations()
```

Returns:

```text
summary
recommended_preview
recommended_total
engine
count
```

It filters recommended items where:

```php
$item['recommendation'] === true
```

Then sorts them by priority:

```text
CRITICAL
HIGH
MEDIUM
LOW
NONE
```

Preview limit:

```text
5
```

## StockIntelligenceDashboardExplanationTool

Calls the same stock intelligence service and returns:

```text
summary
recommended_total
critical_preview
high_preview
engine
count
```

It separates:

```text
critical recommendations
high-priority recommendations
```

for explanation preview.
