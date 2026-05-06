# Rabbit — Next Roadmap After Stock Intelligence V1

## Current Checkpoint

Stock Intelligence V1 is stable enough to document and move forward.

Completed:

- Endpoint stable
- Filters working
- Model classes working
- Model eligibility working
- Outlier/lumpy detection working
- SES working
- Croston/SBA working
- Moving average fallback working
- Regression trend diagnostic working
- Seasonality diagnostic working

## Recommended Next Steps

### 1. Documentation Checkpoint

Document architecture and behavior before adding more modules.

### 2. Dashboard Integration

Owner-only stock intelligence dashboard should show:

- Critical reorder items
- High priority reorder items
- Estimated reorder value
- Low-confidence items
- No-history items
- Lumpy/outlier demand items
- Intermittent-demand items
- Vital products at risk

### 3. Promotions Module

Basic tables:

```text
promotions
promotion_products
```

Purpose:

- Active promotions
- Upcoming promotions
- Product-level discount context
- Future demand-driver signal

### 4. Reviews Module

Basic table:

```text
product_reviews
```

Purpose:

- Product feedback
- Ratings
- Quality signals
- Chatbot/product page explanations

### 5. Chatbot Layer

Rabbit chatbot should be:

```text
A role-aware operational assistant with navigation support.
```

Responsibilities:

- Explain stock/reorder decisions
- Summarize RFQs/orders/notifications
- Help users navigate products and relations
- Trigger safe page navigation
- Answer only from backend-exposed data
- Respect strict role permissions

## Chatbot Grounding Rule

No backend data means no confident answer.

The chatbot must use backend fields such as:

- `selected_model`
- `model_reason`
- `model_eligibility`
- `reason_codes`
- `data_quality_flags`
- `trend_analysis`
- `seasonality_analysis`
- `outlier_analysis`

It must not invent product, supplier, stock, or forecasting facts.
