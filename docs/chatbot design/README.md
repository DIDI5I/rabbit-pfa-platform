# Rabbit Chatbot V1 Design Documentation

This package documents the finalized initial design for the Rabbit chatbot/backend intelligence layer.

The chatbot is designed as a controlled backend assistant, not a fully autonomous agent.

## Core Design

Rabbit Chatbot V1 is:

- role-aware
- tool-aware
- memory-aware
- access-aware
- backend-controlled
- read-only first
- deterministic-first
- API-assisted only when needed

## Main Principle

AI can assist classification and wording, but the backend owns:

- identity
- role detection
- permission checks
- tool selection
- tool execution
- security verification
- response formatting

## Backend Response Convention

All normal chatbot responses must follow:

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

- `chatbot_v1_controlled_flow.md`
- `role_based_access_design.md`
- `tool_memory_access_policy.md`
- `response_contract.md`
- `navigation_ux_contract.md`
- `implementation_next_steps.md`


## Flowchart Correction Status

The package now includes a corrected flowchart source file:

```text
rabbit_chatbot_v1_controlled_flow_corrected_mermaid.md
```

Use that Mermaid file as the official corrected flowchart source. The PNG image is kept as a visual reference, but the corrected Mermaid source contains the final label corrections.
