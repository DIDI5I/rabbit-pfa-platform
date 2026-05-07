# Expire RFQ Write Action

Intent:

```text
expire_rfq
```

Allowed when:

```text
role = owner
allowed_actions contains expire
```

Impact:

```text
RFQ status changes to expired
RFQ is closed
further quoting is prevented
```

Tested successfully with RFQ #5.
