# Rabbit Chatbot — EntityResolver + Clarification Memory Checkpoint

This package documents the Rabbit chatbot checkpoint after implementing:

```text
EntityResolver V1
Clarification follow-up memory V1
Human-friendly answer labels using product/component names + SKUs
```

## Current Status

The chatbot can now resolve product/component references by:

```text
numeric ID
exact SKU
partial name/search term
```

And when a reference is ambiguous, it asks the user to clarify, stores the pending context in session memory, and continues the original intent when the user replies with an ID/SKU/ordinal.

## Core Rule

```text
Still read-only.
Still role-protected.
Still backend-tool grounded.
Still no AI fallback.
Still no write actions.
```
