# Next Stage Options

## Recommended next stage

```text
Stage 11A.3 — Write-action audit logging
```

Add a dedicated logger for confirmed/cancelled write actions.

Fields:

```text
created_at
user_id
role
intent
tool
action_id
status
risk_level
confirmed
executed
cancelled
params_summary
```

Do not log secrets or raw payloads.

## Alternative next stage

```text
Stage 11B — RFQ write actions
```

Possible actions:

```text
accept RFQ
reject RFQ
quote RFQ
cancel RFQ
```

These are higher risk than notification actions and must use the same preview/confirm/execute pipeline.
