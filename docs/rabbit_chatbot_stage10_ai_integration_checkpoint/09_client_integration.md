# OpenAI-Compatible Client

Stage 10 uses a generic OpenAI-compatible chat completions client:

```text
OpenAiCompatibleClient
```

It supports Groq through:

```text
AI_PROVIDER=groq
AI_API_URL=https://api.groq.com/openai/v1/chat/completions
AI_MODEL=openai/gpt-oss-120b
```

Request shape:

```json
{
  "model": "openai/gpt-oss-120b",
  "messages": [
    {
      "role": "system",
      "content": "..."
    },
    {
      "role": "user",
      "content": "..."
    }
  ],
  "temperature": 0.2,
  "max_completion_tokens": 280,
  "reasoning_effort": "low",
  "reasoning_format": "hidden"
}
```
