# WriteActionLogger

`WriteActionLogger` records safe metadata for write-action lifecycle events.

Logged fields:

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
error
```

The logger sanitizes `params_summary` and redacts sensitive keys such as:

```text
password
api_key
token
secret
authorization
cookie
session
```

It does not log full request payloads.
