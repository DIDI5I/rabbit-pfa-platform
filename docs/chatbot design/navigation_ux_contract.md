# Navigation UX Contract — Corrected

Rabbit chatbot can return navigation results.

Navigation is not a backend write action. It is a frontend instruction.

## Operation Type

```text
operation_type = navigation
```

## Backend Response Example

```json
{
  "message": "Navigation target resolved.",
  "data": {
    "answer": "Opening the inventory page.",
    "intent": "navigate_inventory",
    "confidence": "high",
    "role": "owner",
    "operation_type": "navigation",
    "navigation": {
      "navigation_type": "internal_page",
      "target_page": "/owner/inventory",
      "previous_page_link": "/owner/dashboard",
      "bubble_text": "Back to previous page",
      "bubble_duration_ms": 4000
    },
    "sources": [],
    "limitations": [],
    "suggested_actions": []
  }
}
```

## Frontend Behavior

When the frontend receives a navigation result:

1. Navigate to `target_page`.
2. Show a temporary floating bubble.
3. Bubble text should default to:

```text
Back to previous page
```

4. Bubble should remain visible for a few seconds.
5. Suggested default duration:

```text
4000 ms
```

6. Bubble should allow user to return to `previous_page_link`.
7. Bubble should disappear automatically.

## Future Navigation Types

Potential future values:

```text
internal_page
external_link
modal
tab
drawer
```
