# Clarification Flow

## Step 1 — Ambiguous request

```text
show dependencies for XR200
```

## Step 2 — Intent detection

```text
intent = owner_product_dependencies
params.product_ref = xr200
```

## Step 3 — EntityResolver

Search finds multiple possible matches and returns:

```text
needs_clarification
```

## Step 4 — AnswerComposer

`AnswerComposer::needsClarification()`:

```text
stores pending clarification using ClarificationManager
returns clarification response
```

## Step 5 — User replies

Examples:

```text
1
product 1
ASM-PMP-XR200
third
option 3
```

## Step 6 — ClarificationIntentDetector

If clarification is pending and the message looks like a selection:

```text
intent = clarification_response
```

## Step 7 — ClarificationTool

```text
reads pending context
matches reply to ID/SKU/ordinal
builds new params
checks permission again
clears pending context
re-executes original intent through ToolExecutor
```

## Step 8 — Original presenter handles success

Example final answer:

```text
Pompe centrifuge horizontale XR200 7.5 kW (ASM-PMP-XR200) has 9 registered dependencies. Showing 5.
```
