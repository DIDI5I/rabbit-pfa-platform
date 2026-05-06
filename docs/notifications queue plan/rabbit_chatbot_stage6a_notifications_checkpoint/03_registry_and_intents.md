# Registry and Intent Detection

## NotificationToolRegistry

Registered tools:

```text
notification_summary
unread_notifications
notifications_by_type
```

Allowed roles:

```text
owner
client
supplier
fournisseur
```

Guests are denied.

## NotificationIntentDetector

Example messages:

```text
show my notifications
show unread notifications
show stock alerts
show RFQ notifications
show order notifications
show purchase lot notifications
```

Type mapping:

```text
RFQ -> RFQ_ASSIGNED, RFQ_QUOTED, RFQ_ACCEPTED_BY_OWNER, RFQ_REJECTED_BY_OWNER
STOCK -> LOW_STOCK_ALERT, OUT_OF_STOCK_ALERT
PURCHASE_LOT -> PURCHASE_LOT_NEEDS_FINALIZATION, PURCHASE_LOT_FINALIZED
ORDER -> ORDER_CREATED, ORDER_STATUS_CHANGED
```

## Detector Ordering Fix

`show RFQ notifications` originally matched `rfq_summary`.

Fix:

```php
new NotificationIntentDetector(),
new RfqIntentDetector(),
```
