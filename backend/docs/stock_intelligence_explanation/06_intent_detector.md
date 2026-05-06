# StockIntelligenceExplanationIntentDetector

The detector recognizes messages such as:

```text
why should I reorder ASM-PMP-XR200?
why reorder XR200?
explain reorder for product 1
explain stock intelligence for ASM-PMP-XR200
stock intelligence for CMP-HYD-FLT10
what model was used for CMP-BRG-6205?
why is confidence LOW for XR200?
why is this high priority for XR200?
explain recommendation for CMP-BRG-6205
```

It extracts either:

```text
product_id
```

or:

```text
product_ref
```

Then the tool uses `EntityResolver` to resolve the product safely.

Detector ordering note:

This detector should run before generic reorder/inventory detectors so explanation questions do not get routed to the basic reorder list tool.
