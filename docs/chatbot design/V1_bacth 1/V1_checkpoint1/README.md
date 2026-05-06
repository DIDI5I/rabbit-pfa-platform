# Rabbit Chatbot V1 Implementation Checkpoint

This package documents the current implementation state after Batch 1 chatbot foundation, intent refactor, natural search fixes, and result shaping.

## Current Status

The Rabbit chatbot V1 now supports:

- public `/chatbot/ask` route
- guest, client, supplier/fournisseur, and owner role identification
- session-based identity resolution
- role-based permission checks
- read-only Batch 1 tools
- role-authorized navigation
- modular intent detectors
- public catalogue search
- active promotions
- owner inventory summary
- owner inventory alerts
- owner cost rollup
- owner reorder recommendations
- owner stock dashboard summary
- capped chatbot previews using:
  - `answer`
  - `summary`
  - `items_preview`
  - `result_meta`

## Core Rule

```text
Chatbot V1 = read-only tools + role-authorized navigation only.
```

Write actions remain blocked.

## Backend Response Convention

All successful chatbot responses follow:

```json
{
  "message": "...",
  "data": {}
}
```

The chatbot response body now avoids large raw result arrays by default.
