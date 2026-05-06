# .gitignore Updates

The repository should ignore local config and logs:

```gitignore
# Local configuration / secrets
config.php

# Environment files
.env
.env.*

# Logs
*.log
logs/
storage/logs/
storage/logs/*.log

# Dependencies
/vendor/
/node_modules/

# OS / editor
.DS_Store
Thumbs.db
.vscode/
.idea/
```

If `config.php` was already tracked, the command would be:

```powershell
git rm --cached config.php
```

In this project, `config.php` was not tracked.
