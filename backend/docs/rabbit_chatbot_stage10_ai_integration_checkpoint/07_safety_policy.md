# AiSafetyPolicy

`AiSafetyPolicy` controls whether a response can be refined and validates AI output.

## Refinement Skip Rules

The policy skips AI refinement for statuses such as:

```text
denied
failed
not_found
missing_params
validation_failed
clarification_needed
ambiguous
entity_not_found
entity_resolution_failed
```

It also skips blocked intents such as:

```text
navigate
help
clarification
show_more
```

## Output Validation

The policy checks AI output for:

```text
SQL-like patterns
write-action claims
unknown numeric values
unknown SKU-like tokens
```

If validation fails and fallback is enabled:

```text
Rabbit discards the AI answer and returns the deterministic answer.
```
