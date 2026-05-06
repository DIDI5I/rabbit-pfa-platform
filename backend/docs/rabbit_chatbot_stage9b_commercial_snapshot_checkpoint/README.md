# Rabbit Chatbot — Stage 9B Commercial Product Intelligence Checkpoint

Status: PASSED

Stage 9B extends the existing `product_intelligence_snapshot` tool with commercial catalogue intelligence.

Added commercial fields:

```text
active_promotions_count
active_promotions_preview
review_count
average_rating
rating_distribution
reviews_preview
```

The snapshot now combines:

```text
product identity
product relationships / recommendations
commercial signals
owner-only stock intelligence
data quality flags
role-based sanitization
```

Core rule remains:

```text
Commercial catalogue data is public-safe.
Internal stock, cost, procurement, supplier, and reorder data stay owner-only.
```
