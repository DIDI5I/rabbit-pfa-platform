# AiRefinementLogger

A separate AI log file was added:

```text
storage/logs/chatbot_ai.log
```

The logger records only safe metadata:

```text
created_at
user_id
role
intent
tool
ai_refined
provider
model
prompt_version
fallback_reason
input_char_count
output_char_count
validation_passed
validation_violations
error
```

It does not log:

```text
API key
system prompt
user prompt
safe_data payload
full backend response
full AI response
```

This keeps AI observability without leaking secrets or sensitive payloads.
