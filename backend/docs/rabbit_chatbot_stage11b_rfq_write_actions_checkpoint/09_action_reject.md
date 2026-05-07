# Reject RFQ Write Action

Intent:

```text
reject_rfq
```

Allowed when:

```text
role = owner
RFQ status = quoted
allowed_actions contains reject
```

Impact:

```text
RFQ status changes to rejected
supplier may be notified
```

Tested successfully with RFQ #6.
