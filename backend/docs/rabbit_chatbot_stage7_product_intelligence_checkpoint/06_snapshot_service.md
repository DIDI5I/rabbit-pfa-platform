# ProductIntelligenceSnapshotService

File:

```text
app/Services/ProductIntelligenceSnapshotService.php
```

Responsibility:

```text
fetch product
fetch recommendation/relationship data
fetch owner-only reorder data
sanitize by role
build data_quality flags
```

V1 snapshot:

```text
product
product_relationships
reorder
data_quality
```

Product relationships include counts, groups, grounding, and recommendation note.

Owner reorder includes availability, recommendation, priority, confidence, reason codes, and raw summary.
