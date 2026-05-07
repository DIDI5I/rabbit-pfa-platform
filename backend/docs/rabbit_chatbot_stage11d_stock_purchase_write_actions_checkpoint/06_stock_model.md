# Stock Model

Stock is movement-based.

Current stock is calculated as:

```text
SUM(IN movements) - SUM(OUT movements)
```

The chatbot does not edit `components.stock_qty`.

It creates rows in:

```text
stock_movements
```

Then stock summary endpoints recalculate current stock from movements.
