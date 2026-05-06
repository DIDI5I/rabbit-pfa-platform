# Security Notes

The write-action audit log should not contain:

```text
API keys
DB passwords
raw prompts
full AI payloads
full backend responses
session cookies
authorization headers
```

`.gitignore` should include:

```gitignore
*.log
storage/logs/
storage/logs/*.log
```

Before committing, run:

```powershell
git status --short | findstr /I "config.php .env chatbot.log chatbot_ai.log chatbot_write_actions.log"
```

Expected:

```text
no output
```
