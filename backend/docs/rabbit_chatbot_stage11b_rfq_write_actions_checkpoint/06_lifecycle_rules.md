# RFQ Lifecycle Rules

The chatbot uses:

```php
RfqService::getAllowedActions($id)
```

Observed lifecycle behavior:

```text
owner:
  draft  -> open
  open   -> expire
  quoted -> accept, reject, expire

supplier:
  open   -> quote
```

Chatbot RFQ preview tools refuse actions when the lifecycle does not allow them.
