# Next Stage Options

## Recommended next stage

```text
Stage 11 — Write-action confirmation pipeline
```

Purpose:

Allow controlled actions only after explicit user confirmation.

Examples:

```text
mark notification as read
mark all notifications as read
accept RFQ
reject RFQ
update order status
create RFQ
create purchase lot
update thresholds
```

Required flow:

```text
detect write intent
check permission
prepare action preview
ask for confirmation
execute only after explicit confirmation
audit log
security verification
```

## Alternative next stage

```text
Frontend AI-refined bubble styling
```

Use:

```json
"ai_refined": true
```

to change bubble color or show a subtle badge, without printing the flag inside the message body.
