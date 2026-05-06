# Final Confirmed Behavior

## Normal Mode

Command:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"explain stock intelligence dashboard\"}"
```

Confirmed:

```text
ai_refined = true
result_meta.ai is not exposed
```

## Debug Mode

Command:

```cmd
curl.exe -i -b cookies.txt -X POST "http://localhost:8888/chatbot/ask?debug=1" -H "Content-Type: application/json" -d "{\"message\":\"debug explain stock intelligence dashboard\"}"
```

Confirmed:

```text
provider = groq
model = openai/gpt-oss-120b
prompt_version = v1.0
fallback_reason = null
validation_passed = true
validation_violations = []
```

## Structural Safety

Confirmed:

```text
Only data.answer changes.
summary stays backend-owned.
items_preview stays backend-owned.
result_meta stays backend-owned.
sources stay backend-owned.
limitations stay backend-owned.
suggested_actions stay backend-owned.
SecurityVerifier still runs after AI.
```
