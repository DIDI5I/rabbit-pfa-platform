# Files Added

## Write-action core

```text
app/Services/Chatbot/WriteActions/PendingActionStore.php
app/Services/Chatbot/WriteActions/WriteActionResponseBuilder.php
app/Services/Chatbot/WriteActions/WriteActionExecutor.php
```

## Contract

```text
app/Services/Chatbot/WriteActions/Contracts/PendingActionExecutorInterface.php
```

## Confirmation/cancel detectors

```text
app/Services/Chatbot/Intent/ConfirmActionIntentDetector.php
app/Services/Chatbot/Intent/CancelActionIntentDetector.php
```

## Registry

```text
app/Services/Chatbot/Registry/WriteActionToolRegistry.php
```

## Notification write-action domain

```text
app/Services/Chatbot/WriteActions/Notification/NotificationWriteIntentDetector.php
app/Services/Chatbot/WriteActions/Notification/NotificationWriteToolRegistry.php
app/Services/Chatbot/WriteActions/Notification/MarkAllNotificationsReadPreviewTool.php
app/Services/Chatbot/WriteActions/Notification/MarkNotificationReadPreviewTool.php
app/Services/Chatbot/WriteActions/Notification/MarkAllNotificationsReadExecutor.php
app/Services/Chatbot/WriteActions/Notification/MarkNotificationReadExecutor.php
```

## Updated existing files

```text
app/Services/Chatbot/ChatbotService.php
app/Services/Chatbot/IntentClassifier.php
app/Services/Chatbot/ToolRegistry.php
app/Services/Chatbot/ToolExecutor.php
```
