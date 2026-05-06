# ChatbotService Patch

Pending actions are now cleared after any confirmation attempt.

Before:

```php
if (($result['executed'] ?? false) === true) {
    $store->clear();
}
```

After:

```php
$store->clear();
```

Reason:

```text
If a confirmed action fails because the target is invalid, keeping the pending action is not useful.
The user should not be stuck with a failed pending action.
```
