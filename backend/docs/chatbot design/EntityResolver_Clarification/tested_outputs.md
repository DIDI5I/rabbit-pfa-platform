# Tested Outputs

## ID still works

```text
show purchase lots for product 1
```

Works.

## Exact SKU works

```text
show purchase lots for ASM-PMP-XR200
```

Works.

```text
show stock movements for CMP-HYD-FLT10
```

Works.

## Ambiguity handling works

Input:

```text
show dependencies for XR200
```

Output:

```text
Chatbot needs clarification.
I found multiple possible matches. Which one do you mean?
```

Returned matches included:

```text
id = 16, Arbre pompe inox AISI 420 Ø25 mm, CMP-SHAFT-PMP25
id = 10, Kit maintenance pompe XR200, SUB-MNT-KIT-PMP
id = 1, Pompe centrifuge horizontale XR200 7.5 kW, ASM-PMP-XR200
```

## Clarification reply works

After ambiguity:

```text
1
```

re-runs:

```text
owner_product_dependencies
```

with:

```text
product_id = 1
```

and returns dependencies.

## Human-Friendly Label Fix

After updating EntityResolver ID early return to include `resolved_item`, the answer can use:

```text
Pompe centrifuge horizontale XR200 7.5 kW (ASM-PMP-XR200)
```

instead of:

```text
Product 1
```
