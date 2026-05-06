# Current Architecture Update

## New Pieces Added

```text
app/Services/Chatbot/EntityResolver.php
app/Services/Chatbot/ClarificationPolicy.php
app/Services/Chatbot/ClarificationManager.php
app/Services/Chatbot/Intent/ClarificationIntentDetector.php
app/Services/Chatbot/Tools/ClarificationTool.php
app/Services/Chatbot/Presenters/ClarificationPresenter.php
```

## Responsibility Split

```text
EntityResolver              → resolves ID/SKU/name references
ClarificationManager        → stores pending clarification in session
ClarificationIntentDetector → detects replies like 1, product 1, ASM-PMP-XR200
ClarificationTool           → re-executes original intent after clarification
ClarificationPresenter      → handles failed clarification replies
```

## Main Flow

```text
ChatbotController
    ↓
ChatbotService
    ↓
IdentityResolver
    ↓
IntentClassifier
    ↓
ToolRegistry
    ↓
OperationClassifier
    ↓
PermissionGuard
    ↓
ToolExecutor
    ↓
ChatbotLogger
    ↓
MemoryManager
    ↓
AnswerComposer
    ↓
SecurityVerifier
    ↓
Response
```
