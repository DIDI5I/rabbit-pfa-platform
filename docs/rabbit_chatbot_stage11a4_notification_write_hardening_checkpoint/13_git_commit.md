# Git Commit

Recommended commit:

```powershell
git add app/Repositories/Repository.php app/Repositories/NotificationRepository.php app/Services/NotificationService.php app/Services/Chatbot/WriteActions/Notification/MarkAllNotificationsReadPreviewTool.php app/Services/Chatbot/WriteActions/Notification/MarkNotificationReadExecutor.php app/Services/Chatbot/WriteActions/WriteActionResponseBuilder.php app/Services/Chatbot/ChatbotService.php

git commit -m "Harden notification chatbot write actions"
```

Before committing, check no secrets/logs are staged:

```powershell
git status --short | findstr /I "config.php .env chatbot.log chatbot_ai.log chatbot_write_actions.log"
```

Expected:

```text
no output
```
