# Stock Impact Preview

For processing:

```json
{
  "stock_impact": {
    "type": "sale_stock_out",
    "items": [
      {
        "component_id": 34,
        "component_name": "Feuille EPDM 3 mm",
        "component_sku": "RAW-GSK-EPDM3",
        "quantity": 30
      }
    ]
  }
}
```

For cancelled:

```json
{
  "stock_impact": {
    "type": "possible_stock_restore",
    "items": [
      {
        "component_id": 34,
        "component_name": "Feuille EPDM 3 mm",
        "component_sku": "RAW-GSK-EPDM3",
        "quantity": 30
      }
    ],
    "note": "Stock will be restored only if this order previously generated sale stock-out movements and has not already been restored."
  }
}
```

This makes high-risk order actions explain their inventory impact before execution.
