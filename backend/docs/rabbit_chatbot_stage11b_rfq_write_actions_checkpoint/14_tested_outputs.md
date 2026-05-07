# Tested Outputs Summary

## Reject RFQ

```text
reject RFQ 6
confirm
GET /rfqs/6
```

Result:

```text
RFQ #6 rejected
executed = true
status = rejected
```

## Accept RFQ

```text
accept RFQ 13
confirm
GET /rfqs/13
GET /purchase-lots
```

Result:

```text
RFQ #13 accepted
purchase lot #45 created
reference_type = rfq
reference_id = 13
status = draft
supplier = Atlas Industrial Supply SARL
quantity = 20
unit price = 44.50
total = 890
```

## Expire RFQ

```text
expire RFQ 5
confirm
```

Result:

```text
RFQ #5 expired
executed = true
```

## Open RFQ

```text
open draft RFQ
confirm
```

Result:

```text
RFQ opened successfully
```
