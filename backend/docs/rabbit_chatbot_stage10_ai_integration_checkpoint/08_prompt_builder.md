# AiPromptBuilder

The prompt builder creates a strict prompt using backend-approved data only.

Core rules:

```text
Use only the provided backend response data.
Do not invent products, suppliers, prices, quantities, dates, relationships, statuses, or recommendations.
Do not mention fields that are not present.
Do not add numeric values not present.
Do not claim write actions were executed.
Do not change permissions or role visibility.
If data is missing, say it is unavailable.
Return only the refined answer text.
```

Prompt version:

```text
v1.0
```

## Output Style Rules Added

```text
Maximum 3 sentences.
Do not use bullet points.
Do not exceed 700 characters.
Use plain ASCII punctuation only.
```
