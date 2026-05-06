# config.php Shape

The local `config.php` should contain real local secrets, but it must not be committed.

Expected local shape:

```php
<?php

return [
    'database' => [
        'host' => 'localhost',
        'dbname' => 'rabbit_db',
        'charset' => 'utf8mb4',
    ],

    'username' => 'root',
    'password' => 'LOCAL_DB_PASSWORD',

    'ai' => [
        'enabled' => false,
        'provider' => 'groq',
        'api_url' => 'https://api.groq.com/openai/v1/chat/completions',
        'api_key' => '',
        'model' => 'openai/gpt-oss-120b',
        'prompt_version' => 'v1.0',
        'timeout_seconds' => 8,
        'max_input_chars' => 12000,
        'max_output_chars' => 800,
        'log_enabled' => true,
        'strict_output_validation' => true,
        'fallback_on_validation_failure' => true,
        'allowed_intents' => [
            'product_intelligence_snapshot',
            'stock_intelligence_explanation',
            'stock_intelligence_summary',
            'stock_intelligence_dashboard_explanation',
        ],
    ],
];
```

Production/demo safe default:

```php
'enabled' => false
```
