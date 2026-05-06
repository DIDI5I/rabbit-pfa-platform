# Cleanup Required Before Final Commit

Before finalizing Stage 10 in Git, do this cleanup:

```text
Remove any hardcoded API key from AiConfig.
Remove temporary hardcoded config from AiConfig::env().
Connect AiConfig to the real config/env loader.
Ensure .env is ignored by Git.
Ensure API keys are never committed.
Keep AI_ENABLED=false by default for safe deployment.
```

Recommended production default:

```env
AI_ENABLED=false
```
