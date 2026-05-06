# Response Schema

Every chatbot response now includes:

```json
"ai_refined": false
```

or:

```json
"ai_refined": true
```

## Normal Mode

Normal mode exposes only the simple boolean:

```json
{
  "data": {
    "answer": "...",
    "ai_refined": true
  }
}
```

The frontend should use this for UI state, such as changing the chat bubble color or showing a small subtle AI badge.

## Debug Mode

Debug mode is enabled with:

```text
?debug=1
```

Debug mode exposes full AI metadata:

```json
"result_meta": {
  "ai": {
    "provider": "groq",
    "model": "openai/gpt-oss-120b",
    "prompt_version": "v1.0",
    "fallback_reason": null,
    "input_char_count": 3018,
    "output_char_count": 376,
    "validation_passed": true,
    "validation_violations": []
  }
}
```
