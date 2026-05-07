# Open RFQ Write Action

Intent:

```text
open_rfq
```

Allowed when:

```text
role = owner
RFQ status = draft
allowed_actions contains open
supplier_id exists
```

Impact:

```text
RFQ status changes to open
supplier can quote
supplier may be notified
```

Expected final answer:

```text
Confirmed. RFQ #<id> was opened.
```
