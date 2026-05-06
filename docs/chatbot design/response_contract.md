# Chatbot Response Contract

All chatbot output must follow Rabbit backend convention.

## Normal Response Shape

```json
{
  "message": "...",
  "data": { }
}
```

## Validation Error Shape

```json
{
  "message": "Validation failed.",
  "errors": { }
}
```

## Standard Chatbot Data Schema

```json
{
  "message": "Chatbot answer generated successfully.",
  "data": {
    "answer": "string|null",
    "intent": "string",
    "confidence": "none|low|medium|high",
    "role": "guest|client|supplier|owner",
    "operation_type": "authentication|read_only|write_action|navigation|unsupported",
    "sources": [
      {
        "tool": "string",
        "status": "used|skipped|denied|failed"
      }
    ],
    "limitations": [],
    "suggested_actions": []
  }
}
```

## Success Example

```json
{
  "message": "Chatbot answer generated successfully.",
  "data": {
    "answer": "There are 5 inventory alerts: 2 products are out of stock and 3 products are low stock.",
    "intent": "inventory_alerts",
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

## Permission Denied Example

```json
{
  "message": "Chatbot could not answer this request.",
  "data": {
    "answer": "You do not have permission to access this information.",
    "intent": "inventory_alerts",
    "confidence": "high",
    "role": "client",
    "operation_type": "read_only",
    "sources": [],
    "limitations": [
      "This request requires owner access."
    ],
    "suggested_actions": [
      "Ask about catalogue products",
      "Ask about active promotions",
      "Ask about your own orders"
    ]
  }
}
```

## Unsupported / Unknown Example

```json
{
  "message": "Chatbot could not answer this request.",
  "data": {
    "answer": "I could not understand that request safely. Please try asking in a more specific way.",
    "intent": "unknown",
    "confidence": "none",
    "role": "client",
    "operation_type": "unsupported",
    "sources": [],
    "limitations": [
      "No valid intent matched the request."
    ],
    "suggested_actions": []
  }
}
```

## Write Action Disabled Example

```json
{
  "message": "Chatbot action not supported yet.",
  "data": {
    "answer": "I can retrieve and explain information, but I cannot perform write actions yet.",
    "intent": "submit_quote",
    "confidence": "high",
    "role": "supplier",
    "operation_type": "write_action",
    "sources": [],
    "limitations": [
      "Write actions are disabled in Chatbot V1."
    ],
    "suggested_actions": [
      "Use the RFQ quote form"
    ]
  }
}
```
