# Rabbit Chatbot — Stage 7 Product Intelligence Snapshot Checkpoint

Status: PASSED

Stage 7 adds the aggregated chatbot tool:

```text
product_intelligence_snapshot
```

It combines product identity, product relationships/recommendations, owner-only reorder intelligence, data quality flags, and role-based sanitization.

Core rule:

```text
Product relationships do not create stock movements.
Product relationships do not consume child stock.
Product relationships do not mean production or assembly.
```

They support maintenance support, spare part navigation, compatible part lookup, customer recommendations, commercial alternatives, cross-selling, procurement decisions, and AI/product assistant responses.
