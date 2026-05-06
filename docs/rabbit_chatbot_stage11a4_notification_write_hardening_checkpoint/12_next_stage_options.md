# Next Stage Options

## Recommended next stage

```text
Stage 11B — RFQ write actions
```

Possible first actions:

```text
accept RFQ
reject RFQ
cancel RFQ
mark RFQ as ordered
```

These should use the same architecture:

```text
intent detection
permission guard
preview
pending action store
confirm/cancel
domain executor
backend service call
audit log
security verification
```

## Optional before 11B

Add direct preview validation for single notification IDs:

```text
find notification by ID for current user/role before creating pending action
```

This would avoid creating a preview for notification IDs that are already invalid.
