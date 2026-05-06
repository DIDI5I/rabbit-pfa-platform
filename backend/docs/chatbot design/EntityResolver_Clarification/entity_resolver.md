# EntityResolver V1

## File

```text
app/Services/Chatbot/EntityResolver.php
```

## Purpose

Resolve user references into backend IDs.

## Supported Entity Types

```text
product
component
```

Because Rabbit stocks all levels directly, products/components are resolved through catalogue product data.

## Supported Reference Types

```text
numeric ID
exact SKU
partial name
search term
```

## Product Resolution

Typical params accepted:

```php
['product_id' => 1]
```

or:

```php
['product_ref' => 'ASM-PMP-XR200']
```

or:

```php
['product_ref' => 'XR200']
```

## Component Resolution

Typical params accepted:

```php
['component_id' => 27]
```

or:

```php
['component_ref' => 'CMP-HYD-FLT10']
```

## Resolution Statuses

```text
resolved
missing_reference
not_found
needs_clarification
```

## No Guessing Rule

If multiple matches are found, EntityResolver must not guess.

It returns:

```text
needs_clarification
```

with up to 5 candidate matches.

## ID Resolution Fix

Originally ID resolution returned only the ID, so presenters still said:

```text
Product 1
Component 27
```

Fix:

```text
EntityResolver now fetches the item by ID and includes resolved_item.
```

This allows presenters to say:

```text
Pompe centrifuge horizontale XR200 7.5 kW (ASM-PMP-XR200)
Filtre retour hydraulique 10 microns (CMP-HYD-FLT10)
```
