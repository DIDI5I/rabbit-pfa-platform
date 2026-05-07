# Git Commit Notes

Recommended secret check:

```powershell
git status --short | findstr /I "frontend/.env config.php cookies.txt owner_cookies.txt supplier_cookies.txt new_supplier_cookies.txt chatbot.log chatbot_ai.log chatbot_write_actions.log"
```

Expected:

```text
no output
```

Recommended add:

```powershell
git add backend/app/Services/Chatbot/WriteActions/Stock backend/app/Services/Chatbot/WriteActions/PurchaseLot backend/app/Services/Chatbot/WriteActions/WriteActionExecutor.php backend/app/Services/Chatbot/ToolExecutor.php backend/app/Services/Chatbot/ChatbotService.php backend/app/Services/Chatbot/Registry/WriteActionToolRegistry.php backend/app/Services/Chatbot/IntentClassifier.php backend/app/Services/StockService.php
```

If checkpoint docs are copied into the repo:

```powershell
git add backend/docs/rabbit_chatbot_stage11d_stock_purchase_write_actions_checkpoint
```

Commit:

```powershell
git commit -m "Add stock and purchase chatbot write actions"
```
