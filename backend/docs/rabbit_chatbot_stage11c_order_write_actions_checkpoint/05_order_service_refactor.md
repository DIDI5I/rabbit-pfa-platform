# OrderService Refactor

Before Stage 11C, order status updates used:

```php
updateStatus(int $orderId, UpdateOrderStatusRequest $request)
```

Stage 11C added:

```php
updateStatusByData(int $orderId, string $newStatus)
```

The controller path remains:

```php
updateStatus($orderId, $request)
→ updateStatusByData($orderId, $request->status())
```

This means:

```text
HTTP controller and chatbot executor share the same business logic.
```

The service also validates allowed statuses internally, because chatbot execution bypasses the request class.
