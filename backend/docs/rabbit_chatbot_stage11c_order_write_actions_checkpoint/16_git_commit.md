# Git Commit Notes

Recommended commit:

```powershell
git add backend/app/Services/Chatbot/WriteActions/Order backend/app/Services/Chatbot/WriteActions/WriteActionExecutor.php backend/app/Services/Chatbot/ToolExecutor.php backend/app/Services/Chatbot/ChatbotService.php backend/app/Services/Chatbot/Registry/WriteActionToolRegistry.php backend/app/Services/Chatbot/IntentClassifier.php backend/app/Services/OrderService.php

git commit -m "Add order chatbot write actions"
```

Before committing, check no secrets/logs are staged:

```powershell
git status --short | findstr /I "frontend/.env config.php cookies.txt owner_cookies.txt supplier_cookies.txt chatbot.log chatbot_ai.log chatbot_write_actions.log"
```

Do not commit:

```text
frontend/.env
cookies.txt
owner_cookies.txt
supplier_cookies.txt
new_supplier_cookies.txt
backend/vendor/
logs
config.php
```
