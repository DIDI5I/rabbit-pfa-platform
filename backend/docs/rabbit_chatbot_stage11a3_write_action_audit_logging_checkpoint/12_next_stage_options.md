# Next Stage Options

## Recommended next stage

```text
Stage 11A.4 — Notification write hardening
```

Tasks:

```text
avoid creating pending action when unread_count = 0
check affected rows for markAsRead
return notification_not_found_or_not_accessible when ID is invalid
optionally preview notification title before marking one as read
```

## Alternative next stage

```text
Stage 11B — RFQ write actions
```

Possible actions:

```text
accept RFQ
reject RFQ
cancel RFQ
quote RFQ
convert RFQ to purchase lot
```

These are higher risk and must use the same preview-confirm-execute-audit pipeline.
