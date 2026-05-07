# Next Stage

Recommended next stage:

```text
Stage 11C — Order write actions
```

Candidate actions:

```text
mark order as processing
mark order as shipped
mark order as delivered
cancel order
```

Important risk:

```text
Order status changes may create or reverse stock movements.
```

Alternative pause point:

```text
Freeze backend after Stage 11B and let frontend catch up.
```
