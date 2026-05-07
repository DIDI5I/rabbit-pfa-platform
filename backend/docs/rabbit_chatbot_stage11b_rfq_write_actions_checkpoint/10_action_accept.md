# Accept RFQ Write Action

Intent:

```text
accept_rfq
```

Allowed when:

```text
role = owner
RFQ status = quoted
allowed_actions contains accept
supplier_id exists
quoted_price exists
```

Impact:

```text
RFQ status changes to accepted
purchase lot is created
supplier is notified
owner is notified to finalize purchase lot
```

Tested successfully with RFQ #13.
