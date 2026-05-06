# Stage 11A.3 Goal

The goal of Stage 11A.3 was to make the write-action pipeline auditable.

Before this stage, Rabbit could create pending write actions, ask for confirmation, execute notification writes after confirmation, and cancel pending actions.

After this stage, Rabbit also logs write-action lifecycle events:

```text
preview_created
confirmed
executed
cancelled
expired
```
