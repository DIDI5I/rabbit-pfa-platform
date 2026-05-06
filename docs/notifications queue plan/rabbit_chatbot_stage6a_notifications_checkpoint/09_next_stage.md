# Next Stage Recommendation

Recommended next stage:

```text
Stage 7 — Product Intelligence Snapshot
```

Reason:

```text
It prepares the system for safe API AI summarization.
It supports complex questions like "give me the full picture for XR200".
It prevents the future AI from inventing joins between product, stock, cost, RFQ, reviews, and promotions.
```

Possible first tool:

```text
product_intelligence_snapshot
```

Possible response sections:

```text
product
stock
cost
reorder
procurement
commercial
dependencies
data_quality
```

Role safety:

```text
owner sees internal cost/procurement/reorder sections
client/guest sees public/commercial-safe sections
supplier sees supplier-relevant sections only
```
