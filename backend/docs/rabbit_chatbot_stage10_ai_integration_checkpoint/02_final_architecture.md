# Final Stage 10 Architecture

The final response flow is:

```text
Frontend
  ↓
POST /chatbot/ask
  ↓
ChatbotController
  ↓
ChatbotService
  ↓
IdentityResolver
  ↓
IntentClassifier
  ↓
ToolRegistry / OperationClassifier
  ↓
PermissionGuard
  ↓
ToolExecutor
  ↓
Backend services
  ↓
AnswerComposer / Presenter
  ↓
AiAnswerRefiner
  ↓
AiSafetyPolicy output validation
  ↓
SecurityVerifier
  ↓
JSON response
```

The AI refiner runs after the deterministic presenter and before the SecurityVerifier.
