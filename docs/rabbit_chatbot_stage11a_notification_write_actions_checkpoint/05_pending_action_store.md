# PendingActionStore

`PendingActionStore` stores one pending write action per session.

Stored data includes:

```text
created_at
user_id
role
action_id
intent
tool
operation_type
title
description
params
preview
risk_level
expires_at
```

Safety rules:

```text
pending action belongs to same user/session/role
pending action expires after 5 minutes
cancel clears the pending action
successful execution clears the pending action
```

This prevents cross-user confirmation and stale action execution.
