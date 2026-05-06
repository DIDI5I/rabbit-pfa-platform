# Registry and Detector

Registered tool:

```text
product_intelligence_snapshot
```

Allowed roles:

```text
owner
client
supplier
fournisseur
guest
```

Guest is allowed because the snapshot service sanitizes output by role.

Recognized examples:

```text
product intelligence for ASM-PMP-XR200
debug product intelligence for product 1
full picture for XR200
what do we know about ASM-PMP-XR200
is product 1 healthy
```

The detector extracts `product_id` or `product_ref`, then the tool uses `EntityResolver`.
