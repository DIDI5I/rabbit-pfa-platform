# Set Stock Level / Manual Adjustment

Intent:

```text
set_stock_level
```

Examples:

```text
set stock of component 11 to 120
set stock of component 11 to 115
```

Logic:

```text
target_stock - current_stock = difference

difference > 0 → stock IN movement
difference < 0 → stock OUT movement
difference = 0 → no action needed
```

Test increase:

```text
current_stock = 116
target_stock = 120
difference = +4
movement_type = in
current_stock became 120
```

Test decrease:

```text
current_stock = 120
target_stock = 115
difference = -5
movement_type = out
current_stock became 115
```

No-op test:

```text
set stock of component 11 to 115
→ already 115
→ pending_action = false
```
