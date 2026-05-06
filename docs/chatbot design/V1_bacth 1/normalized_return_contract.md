# Normalized ToolExecutor Return Contract

This is the internal return shape from `ToolExecutor` to `AnswerComposer`.

It is not the final HTTP response.

## Universal Shape

```php
[
    'tool' => 'tool_name',
    'status' => 'success|empty|missing_params|permission_denied|not_found|failed|unsupported',
    'data' => [],
    'meta' => [
        'intent' => 'intent_name',
        'operation_type' => 'read_only|navigation',
        'role_scope' => 'guest|client|supplier|owner',
        'sensitive' => true|false,
        'count' => null,
    ],
    'errors' => [],
]
```

## Status Meanings

```text
success            tool executed and returned usable data
empty              tool executed but no data found
missing_params     required params missing
permission_denied  role/scope rejected before execution
not_found          requested entity does not exist
failed             backend/service exception or unexpected failure
unsupported        tool exists conceptually but is disabled in V1
```

## Final Response Conversion

The `AnswerComposer` converts normalized tool results into Rabbit backend convention:

```json
{
  "message": "Chatbot answer generated successfully.",
  "data": {
    "answer": "...",
    "intent": "...",
    "confidence": "high",
    "role": "owner",
    "operation_type": "read_only",
    "sources": [
      {
        "tool": "inventory_alerts",
        "status": "used"
      }
    ],
    "limitations": [],
    "suggested_actions": []
  }
}
```
