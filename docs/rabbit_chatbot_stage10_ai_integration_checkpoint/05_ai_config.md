# AI Configuration

Stage 10 uses these config values:

```env
AI_ENABLED=true
AI_PROVIDER=groq
AI_API_URL=https://api.groq.com/openai/v1/chat/completions
AI_API_KEY=...
AI_MODEL=openai/gpt-oss-120b
AI_PROMPT_VERSION=v1.0
AI_TIMEOUT_SECONDS=8
AI_MAX_INPUT_CHARS=12000
AI_MAX_OUTPUT_CHARS=800
AI_LOG_ENABLED=true
AI_STRICT_OUTPUT_VALIDATION=true
AI_FALLBACK_ON_VALIDATION_FAILURE=true
AI_ALLOWED_INTENTS=product_intelligence_snapshot,stock_intelligence_explanation,stock_intelligence_summary,stock_intelligence_dashboard_explanation
```

During testing, temporary hardcoded config was used inside `AiConfig::env()` because PHP was not loading the `.env` values.

Important cleanup:

```text
Remove the hardcoded API key/config from AiConfig.
Connect AiConfig to the real environment/config loader.
Never commit API keys to code.
```
