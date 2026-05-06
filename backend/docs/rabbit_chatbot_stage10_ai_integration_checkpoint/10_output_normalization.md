# AI Output Normalization

The AI initially returned Unicode punctuation such as:

```text
em dash
narrow no-break space
non-breaking hyphen
```

A normalizer was added after receiving the AI answer:

```php
$aiAnswer = $this->normalizeAiText($aiAnswer);
```

The normalizer converts:

```text
non-breaking spaces -> normal spaces
em/en dashes -> plain spaced hyphen separators
non-breaking hyphens -> normal hyphens
multiple whitespace -> single spaces
```

Final tested output became clean:

```text
recommended for reorder - two critical and one high-priority
```
