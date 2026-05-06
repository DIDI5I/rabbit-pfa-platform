# Repository Patch

A generic affected-row helper was added to the base repository:

```php
public function affectedRows(): int
{
    return $this->statement?->rowCount() ?? 0;
}
```

This allows update queries to report whether they actually changed any row.
