# Implemented File Architecture

## Controller

```text
app/
  Controllers/
    ChatbotController.php
```

## Chatbot Services

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
```

## Refactored Intent Detectors

```text
app/
  Services/
    Chatbot/
      Intent/
        IntentDetectorInterface.php
        HelpIntentDetector.php
        NavigationIntentDetector.php
        InventoryIntentDetector.php
        CostIntentDetector.php
        ReorderIntentDetector.php
        DashboardIntentDetector.php
        PromotionIntentDetector.php
        CatalogIntentDetector.php
        TextIntentUtils.php
```

## Not Yet Implemented

These are planned later:

```text
MemoryPolicy.php
MemoryManager.php
ChatbotLogger.php
Ai/
  ApiIntentClassifier.php
  ApiAnswerComposer.php
  Provider classes
```
