# Tested Outputs

## Stage 10.1 — AI Foundation

Passed:

```text
successful deterministic response -> ai_refined: false
permission denied response -> ai_refined: false
```

## Stage 10.2 — Prompt Builder + Client Interface

Passed:

```text
debug mode ?debug=1 -> result_meta.ai shown
normal mode -> only ai_refined shown
AI disabled -> fallback_reason = ai_disabled
prompt_version = v1.0
```

## Stage 10.3 — Groq/OpenAI-Compatible Client

### Missing Key Fallback

Passed:

```text
provider = groq
model = openai/gpt-oss-120b
fallback_reason = missing_ai_api_key
ai_refined = false
```

### Real Groq Refinement

Passed for stock intelligence summary:

```text
ai_refined = true
fallback_reason = null
validation_passed = true
validation_violations = []
```

### Dashboard Explanation

Passed after tuning output length and reasoning settings:

```text
ai_refined = true
fallback_reason = null
validation_passed = true
output_char_count = 376
```

### Guest Denial

Passed:

```text
guest stock intelligence summary -> denied
ai_refined = false
fallback_reason = policy_skip
```
