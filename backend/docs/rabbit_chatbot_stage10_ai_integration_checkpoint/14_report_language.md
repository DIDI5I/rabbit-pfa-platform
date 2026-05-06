# Suggested Report/Demo Language

Use this description:

```text
Rabbit is not an autonomous AI agent. It is a controlled backend workflow assistant with optional AI-powered answer refinement. The backend owns intent classification, permissions, tool selection, data access, and structured output. The AI layer only rewrites backend-approved answer text and is blocked from inventing data, bypassing permissions, or performing actions.
```

Useful demo point:

```text
Normal responses expose only ai_refined: true/false for frontend styling. Debug mode exposes provider, model, prompt version, fallback reason, input/output size, and validation status.
```
