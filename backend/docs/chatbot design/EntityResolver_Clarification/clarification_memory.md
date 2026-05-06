# Clarification Follow-Up Memory V1

## Files

```text
app/Services/Chatbot/ClarificationPolicy.php
app/Services/Chatbot/ClarificationManager.php
app/Services/Chatbot/Intent/ClarificationIntentDetector.php
app/Services/Chatbot/Tools/ClarificationTool.php
app/Services/Chatbot/Presenters/ClarificationPresenter.php
```

## Purpose

Allow the user to answer a clarification question naturally.

Example:

```text
User: show dependencies for XR200
Assistant: I found multiple possible matches. Which one do you mean?
User: 1
Assistant: runs owner_product_dependencies using product_id = 1
```

## Storage

Clarification context is stored only in the PHP session.

Session key:

```text
chatbot_pending_clarification
```

No long-term memory.

## Stored Context

```php
[
    'pending' => true,
    'original_intent' => 'owner_product_dependencies',
    'original_tool' => 'owner_product_dependencies',
    'original_params' => [
        'product_ref' => 'xr200',
    ],
    'entity_type' => 'product',
    'matches' => [
        [
            'id' => 1,
            'name' => 'Pompe centrifuge horizontale XR200 7.5 kW',
            'sku' => 'ASM-PMP-XR200',
        ],
    ],
    'created_at' => '2026-05-05 01:08:26',
]
```

## Allowed Intent Storage

ClarificationPolicy currently allows clarification memory for:

```text
owner_product_details
owner_product_dependencies
purchase_lots_by_product
stock_movements_by_component
```
