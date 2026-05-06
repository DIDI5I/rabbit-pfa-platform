# Safety Notes

## Owner Only

Both Stage 9C tools are owner-only.

Do not expose to:

```text
guest
client
supplier
fournisseur
```

## No Calculations in Chatbot

The chatbot does not calculate:

```text
reorder recommendations
model selection
confidence
priority
data quality flags
estimated reorder value
```

It only presents backend output.

## Sensitive Fields

The dashboard exposes sensitive internal data:

```text
stock risk
estimated reorder value
model/data quality weaknesses
product priority
reorder quantities
```

This justifies owner-only access.
