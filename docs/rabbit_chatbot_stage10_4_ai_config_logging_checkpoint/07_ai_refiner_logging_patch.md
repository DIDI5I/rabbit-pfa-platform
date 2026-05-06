# AiAnswerRefiner Logging Patch

`AiAnswerRefiner` was patched to use:

```php
AiRefinementLogger
```

Successful AI refinement logs:

```text
ai_refined = true
fallback_reason = null
validation_passed = true
```

Fallback logs include reasons such as:

```text
ai_disabled
missing_ai_api_key
policy_skip
ai_output_validation_failed
ai_output_too_long
ai_client_exception
```

The logger is controlled by:

```php
'log_enabled' => true
```

in `config.php`.
