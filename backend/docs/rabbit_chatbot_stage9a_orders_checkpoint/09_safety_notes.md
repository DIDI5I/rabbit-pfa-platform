# Safety Notes

## No Client ID From Text

The chatbot must not parse or pass:

```text
client_id
user_id
owner_id
supplier_id
```

from user text.

The backend session decides scope.

## Read-Only Only

Stage 9A does not implement:

```text
create order
update order status
cancel order
ship order
deliver order
```

These belong to the future write-action confirmation pipeline.

## Supplier Denied in V1

Supplier/fournisseur order visibility is denied unless a future supplier-specific order scope is designed.

## Client Scope

Clients should only see orders where:

```php
(int) $order['client_id'] === Auth::id()
```

This is enforced inside `OrderService`.
