# Accept RFQ Hardening

RFQ #3 exposed incomplete test data:

```text
status = quoted
supplier_id = null
quoted_price = null
```

Fix:

```text
AcceptRfqPreviewTool blocks accept when supplier_id or quoted_price is missing.
```

Correct response:

```text
RFQ #3 cannot be accepted because it is missing supplier or quote data.
```

Correct metadata:

```json
{
  "pending_action": false,
  "executed": false,
  "status": "missing_required_data"
}
```
