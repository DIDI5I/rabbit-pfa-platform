# Tested Config States

The real `config.php` flow was tested with three states.

## 1. AI disabled

Config:

```php
'enabled' => false
```

Expected and confirmed:

```text
ai_refined = false
fallback_reason = ai_disabled
```

## 2. AI enabled but no key

Config:

```php
'enabled' => true
'api_key' => ''
```

Expected and confirmed:

```text
ai_refined = false
fallback_reason = missing_ai_api_key
```

## 3. AI enabled with Groq key

Config:

```php
'enabled' => true
'api_key' => 'local Groq key'
```

Expected and confirmed:

```text
ai_refined = true
fallback_reason = null
validation_passed = true
```
