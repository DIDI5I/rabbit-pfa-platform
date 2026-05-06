# Rabbit Chatbot V1 Resource Mapping Documentation

This package documents the finalized planning checkpoint before implementation.

It covers:

- chatbot file architecture
- Batch 1 resource/tool selection
- normalized ToolExecutor return types
- role access rules
- navigation authorization
- blocked V1 write actions
- implementation order

## Core Rule

Rabbit Chatbot V1 allows:

```text
read-only tools + role-authorized navigation only
```

It does not execute write actions.

## Backend Convention

Final chatbot responses must follow:

```json
{
  "message": "...",
  "data": { }
}
```

Validation input errors must follow:

```json
{
  "message": "Validation failed.",
  "errors": { }
}
```

## Included Files

- `file_architecture.md`
- `batch_1_tool_registry.md`
- `normalized_return_contract.md`
- `batch_1_tool_return_types.md`
- `navigation_authorization.md`
- `blocked_v1_actions.md`
- `implementation_order.md`
