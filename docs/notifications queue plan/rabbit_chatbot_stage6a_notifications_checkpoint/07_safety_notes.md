# Safety Notes

## No User ID From Text

The chatbot must not parse:

```text
user_id
client_id
supplier_id
owner_id
role override
```

Notification scoping is handled by backend session.

## No Write Actions

Stage 6A does not wire:

```text
markAsRead
markAllAsRead
```

Those belong to the future write-action confirmation pipeline.

## Type Filtering

`notifications_by_type` filters only notifications already returned by the scoped service call.
