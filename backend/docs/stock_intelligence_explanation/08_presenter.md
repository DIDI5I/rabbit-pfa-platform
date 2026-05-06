# StockIntelligenceExplanationPresenter

File:

```text
app/Services/Chatbot/Presenters/StockIntelligence/StockIntelligenceExplanationPresenter.php
```

The presenter converts the backend item into:

```text
answer
summary
items_preview
result_meta
sources
limitations
suggested_actions
```

Example answer:

```text
Pompe centrifuge horizontale XR200 7.5 kW (ASM-PMP-XR200) is recommended for reorder. The selected model is threshold_only: Insufficient OUT movement history for statistical estimation. Current stock is 7, reorder point is 10. Recommended reorder quantity is 3. Priority is HIGH and confidence is LOW.
```

Preview sections:

```text
stock_state
demand_history
supply_and_value
explanation
```

Important implementation note:

Use `stockPreview()` instead of `preview()` to avoid method signature conflict with `BaseResponsePresenter::preview()`.
