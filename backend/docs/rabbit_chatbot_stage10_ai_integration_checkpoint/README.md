# Rabbit Chatbot — Stage 10 AI Integration Checkpoint

Status: PASSED

Stage 10 adds optional AI answer refinement to Rabbit's existing deterministic chatbot pipeline.

Core result:

```text
Rabbit now supports controlled AI-refined answers using a Groq/OpenAI-compatible API client.
```

Important rule:

```text
The AI does not decide permissions.
The AI does not choose tools.
The AI does not query the database.
The AI does not rewrite structured backend data.
The AI only refines data.answer after deterministic backend execution.
```

The backend remains the source of truth.
