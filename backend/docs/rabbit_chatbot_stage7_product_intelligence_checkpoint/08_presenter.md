# ProductIntelligenceSnapshotPresenter

File:

```text
app/Services/Chatbot/Presenters/ProductIntelligence/ProductIntelligenceSnapshotPresenter.php
```

Purpose: convert the snapshot into the standard chatbot response shape.

Owner answer example:

```text
Pompe centrifuge horizontale XR200 7.5 kW (ASM-PMP-XR200) has a product intelligence snapshot. It has 9 explicit product relationships. Owner stock intelligence is available with priority HIGH and confidence LOW.
```

Guest answer example:

```text
Pompe centrifuge horizontale XR200 7.5 kW (ASM-PMP-XR200) has a product intelligence snapshot. It has 9 explicit product relationships. Internal stock, cost, procurement, and reorder sections are hidden for your role.
```

Fix applied: renamed custom `preview()` method to `snapshotPreview()` to avoid conflict with `BaseResponsePresenter::preview()`.
