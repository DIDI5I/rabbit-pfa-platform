# Rabbit Chatbot V1 File Architecture

Use a service-based architecture.

## Controller

```text
app/
  Controllers/
    ChatbotController.php
```

The controller is a file, not a folder.

## Services

```text
app/
  Services/
    Chatbot/
      ChatbotService.php
      IdentityResolver.php
      RoleProfileRegistry.php
      IntentClassifier.php
      OperationClassifier.php
      ToolRegistry.php
      PermissionGuard.php
      ToolExecutor.php
      AnswerComposer.php
      SecurityVerifier.php
      NavigationResolver.php
      MemoryPolicy.php
      MemoryManager.php
      ChatbotLogger.php
```

## Optional Later AI Fallback Folder

Do not implement this first.

```text
app/
  Services/
    Chatbot/
      Ai/
        ApiIntentClassifier.php
        ApiAnswerComposer.php
        GroqProvider.php
        GeminiProvider.php
        OllamaProvider.php
```

## Responsibilities

### ChatbotController.php

Receives request, validates input, calls `ChatbotService`.

### ChatbotService.php

Main orchestrator for the chatbot pipeline.

### IdentityResolver.php

Reads authenticated user/session and role.

### RoleProfileRegistry.php

Defines behavior and allowed topics per role.

### IntentClassifier.php

Rule-based deterministic intent parser.

### OperationClassifier.php

Classifies intent as:

```text
read_only
navigation
write_action
unsupported
```

### ToolRegistry.php

Official list of chatbot-accessible tools/resources.

### PermissionGuard.php

Checks role, sensitivity, scope, params, navigation authorization.

### ToolExecutor.php

Executes approved backend services only.

No raw AI SQL.

### AnswerComposer.php

Creates deterministic chatbot answers from normalized tool results.

### SecurityVerifier.php

Final output safety check.

### NavigationResolver.php

Maps role-authorized navigation targets to frontend pages.

### MemoryPolicy.php

Defines what each role may remember.

### MemoryManager.php

Manages short-term session memory.

### ChatbotLogger.php

Logs chatbot pipeline events.
