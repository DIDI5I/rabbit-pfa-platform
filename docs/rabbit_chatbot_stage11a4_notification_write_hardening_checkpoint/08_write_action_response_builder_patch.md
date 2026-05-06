# WriteActionResponseBuilder Patch

`confirmed()` was updated to respect:

```php
$result['executed']
```

Instead of always returning success, it now returns:

```text
Chatbot action executed successfully.
```

only when:

```php
$result['executed'] === true
```

Otherwise it returns:

```text
Chatbot action could not be executed.
```

and sets:

```json
"executed": false
```
