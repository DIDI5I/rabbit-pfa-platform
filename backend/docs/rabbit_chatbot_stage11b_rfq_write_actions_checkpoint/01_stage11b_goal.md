# Stage 11B Goal

The goal of Stage 11B was to extend the safe chatbot write-action architecture from notifications to RFQs.

RFQs are higher risk than notifications because they affect procurement workflows.

Especially:

```text
accept RFQ
→ changes RFQ status
→ creates purchase lot
→ triggers notifications
```

So Stage 11B required strict lifecycle validation and confirmation.
