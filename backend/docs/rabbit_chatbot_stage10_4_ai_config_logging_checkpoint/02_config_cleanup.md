# AI Config Cleanup

Before cleanup, temporary hardcoded AI config was used inside `AiConfig::env()` because PHP was not loading `.env`.

After inspection, the backend was found to load configuration through:

```php
$config = require base_path('config.php');
```

So the correct project-native path became:

```text
config.php -> AiConfig
```

`AiConfig.php` was rewritten to load:

```php
base_path('config.php')
```

and read the `ai` array.
