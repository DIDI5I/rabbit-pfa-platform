# AnswerComposer Result Shaping

## Goal

Stop returning huge raw arrays in chatbot responses.

Old shape included:

```json
{
  "result": {}
}
```

New shape uses:

```json
{
  "answer": "...",
  "summary": {},
  "items_preview": [],
  "result_meta": {}
}
```

## Preview Limit

`AnswerComposer` defines:

```php
private const PREVIEW_LIMIT = 5;
```

List-based tools preview at most 5 items.

## New Successful Response Shape

```json
{
  "message": "Chatbot answer generated successfully.",
  "data": {
    "answer": "...",
    "intent": "...",
    "confidence": "high",
    "role": "owner",
    "operation_type": "read_only",
    "summary": {},
    "items_preview": [],
    "result_meta": {},
    "sources": [
      {
        "tool": "...",
        "status": "used"
      }
    ],
    "limitations": [],
    "suggested_actions": []
  }
}
```

## Result Meta

`result_meta` includes:

```text
has_more
total
shown
page, when paginated
total_pages, when paginated
filters, when relevant
```

## Suggested Actions

When there are more than 5 results:

```text
Ask to see more results
Open catalogue
```

Memory/show-more behavior is not implemented yet.
