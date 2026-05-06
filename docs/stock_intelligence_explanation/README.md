# Rabbit Chatbot — Stage 8 Stock Intelligence Explanation Checkpoint

Status: PASSED

Stage 8 adds an owner-only chatbot explanation layer over the backend Stock Intelligence Engine.

Implemented tool:

```text
stock_intelligence_explanation
```

The chatbot now explains backend numerical stock intelligence outputs such as:

```text
selected_model
model_reason
recommendation
priority
confidence
reason_codes
data_quality_flags
current_stock
low_stock_threshold
safety_stock
reorder_point
recommended_reorder_quantity
outflow history
supplier lead time
estimated reorder value
```

Important rule:

```text
The chatbot does not calculate stock intelligence itself.
It only resolves the product, calls StockIntelligenceService, finds the matching item, and presents the backend output.
```
