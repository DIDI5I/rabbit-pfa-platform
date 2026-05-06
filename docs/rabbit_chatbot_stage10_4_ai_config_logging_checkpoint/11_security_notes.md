# Security Notes

A real database password was pasted during development and should be treated as exposed.

Recommended local cleanup:

```text
change the local DB password
do not commit real passwords
keep config.php ignored
use config.example.php for repo
```

AI key rules:

```text
never paste API keys into chat
never commit API keys
never log API keys
keep AI_ENABLED=false by default unless intentionally testing/demoing
```
