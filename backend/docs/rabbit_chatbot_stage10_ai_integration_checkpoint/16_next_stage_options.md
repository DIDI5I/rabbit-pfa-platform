# Next Stage Options

## Recommended Next Stage

```text
Stage 10.4 — AI config cleanup and logging hardening
```

Tasks:

```text
connect AiConfig to real environment loader
remove hardcoded config/key
add AI call logging table or log file
log prompt_version, provider, model, fallback_reason, validation_passed
ensure no prompt payload with sensitive key is logged
```

## Later Stage

```text
Stage 11 — Write-action confirmation pipeline
```

For actions such as:

```text
accept RFQ
reject RFQ
mark notification as read
update order status
create RFQ
create purchase lot
update thresholds
```
