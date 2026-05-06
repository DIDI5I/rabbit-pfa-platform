# Stage 10 Goal

The goal of Stage 10 was to connect an external AI model without turning Rabbit into an uncontrolled agent.

The AI layer is used only for:

```text
answer wording refinement
clearer explanations
business-facing summaries
```

The AI layer is not used for:

```text
database access
permission decisions
tool selection
write actions
business calculations
invented recommendations
```

Stage 10 turns Rabbit from:

```text
deterministic backend workflow assistant
```

into:

```text
deterministic backend workflow assistant
+ optional AI answer refinement
```
