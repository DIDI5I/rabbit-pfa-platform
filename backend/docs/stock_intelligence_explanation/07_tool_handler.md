# StockIntelligenceExplanationTool

File:

```text
app/Services/Chatbot/Tools/StockIntelligence/StockIntelligenceExplanationTool.php
```

Responsibilities:

```text
resolve product using EntityResolver
call StockIntelligenceService::reorderRecommendations()
find matching item by product_id
return item + engine metadata
```

It does not calculate stock intelligence.

It returns:

```text
product_id
resolution
item
engine.period_days
engine.forecast_days
engine.calculation_mode
engine.summary
count
```

If product resolution fails, the tool returns a safe entity-resolution failure.

If no stock intelligence item exists for the resolved product, it returns not_found.
